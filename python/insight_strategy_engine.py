"""
Insight & Strategy Engine: analyzes cleaned tabular data and produces
human-readable conclusions (insights, risks, strategy recommendations).
Rule-based logic, extensible for future ML/AI models.
No charts or dashboards — structured text output only.
"""
import sys
import json
import os
import re
import pandas as pd
import numpy as np
from datetime import datetime


# --- Helpers ---

def to_native(obj):
    """Convert numpy/pandas types to native Python for JSON."""
    if obj is None:
        return None
    if isinstance(obj, (np.integer, np.intc, np.intp, np.int8, np.int16, np.int32, np.int64)):
        return int(obj)
    if isinstance(obj, (np.floating, np.float16, np.float32, np.float64)):
        return float(obj)
    if isinstance(obj, (np.bool_,)):
        return bool(obj)
    if isinstance(obj, (np.ndarray,)):
        return obj.tolist()
    if isinstance(obj, (pd.Series, pd.Index)):
        return obj.tolist()
    if isinstance(obj, (pd.Timestamp,)):
        return obj.strftime("%Y-%m-%d %H:%M:%S")
    try:
        if pd.isna(obj):
            return None
    except (TypeError, ValueError, AttributeError):
        pass
    if isinstance(obj, dict):
        return {str(k): to_native(v) for k, v in obj.items()}
    if isinstance(obj, (list, tuple)):
        return [to_native(x) for x in obj]
    if isinstance(obj, (str, int, float, bool)):
        return obj
    try:
        return str(obj)
    except Exception:
        return None


def load_file(file_path, file_type):
    """Load file based on type."""
    file_path = os.path.normpath(file_path)
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"File not found: {file_path}")
    allowed = ['csv', 'txt', 'xml', 'xlsx']
    if file_type not in allowed:
        raise ValueError(f"Unsupported file type: {file_type}")
    if file_type == 'csv':
        df = pd.read_csv(file_path)
    elif file_type == 'txt':
        try:
            df = pd.read_csv(file_path, sep=',')
        except Exception:
            try:
                df = pd.read_csv(file_path, sep='\t')
            except Exception:
                df = pd.read_csv(file_path, sep=r'\s+', engine='python')
    elif file_type == 'xml':
        df = pd.read_xml(file_path)
    elif file_type == 'xlsx':
        df = pd.read_excel(file_path, engine='openpyxl')
    else:
        raise ValueError(f"Unsupported file type: {file_type}")
    return df


def is_likely_date_column(col_data):
    """Heuristic: column is likely a date/time if most values parse as dates."""
    sample = col_data.dropna().astype(str).head(min(500, len(col_data)))
    if len(sample) == 0:
        return False
    parsed = 0
    for v in sample:
        v = str(v).strip()
        if not v:
            continue
        try:
            pd.to_datetime(v)
            parsed += 1
        except Exception:
            pass
    return parsed / max(1, len(sample)) > 0.7


def get_numeric_columns(df):
    """List of column names that are predominantly numeric."""
    out = []
    for col in df.columns:
        vals = pd.to_numeric(df[col], errors='coerce')
        if vals.notna().mean() > 0.8:
            out.append(col)
    return out


def get_categorical_columns(df, exclude_dates=True):
    """List of column names that are predominantly categorical (and optionally exclude date-like)."""
    out = []
    for col in df.columns:
        if exclude_dates and is_likely_date_column(df[col]):
            continue
        vals = pd.to_numeric(df[col], errors='coerce')
        if vals.notna().mean() <= 0.8:
            out.append(col)
    return out


# --- Insight Discovery (rule-based) ---

def discover_value_concentration(df, cat_cols, top_n=5, concentration_pct_threshold=25):
    """Detect value concentration: top categories and their share. Returns plain-English findings."""
    findings = []
    for col in cat_cols:
        s = df[col].astype(str).replace('', np.nan).dropna()
        if len(s) == 0:
            continue
        vc = s.value_counts()
        total = vc.sum()
        top = vc.head(top_n)
        for rank, (val, count) in enumerate(top.items(), 1):
            pct = round(100 * count / total, 1)
            if pct >= concentration_pct_threshold:
                val_short = str(val)[:50] + ('…' if len(str(val)) > 50 else '')
                findings.append({
                    "category": "value_concentration",
                    "column": col,
                    "text": f"In the column '{col}', the value '{val_short}' appears in {pct}% of rows (rank {rank} by how often it appears).",
                    "metric_value": pct,
                    "metric_label": "share (%)"
                })
    return findings


def discover_patterns(df, cat_cols, dominance_threshold_pct=50):
    """Dominance and repetition: single value or few values dominating."""
    findings = []
    for col in cat_cols:
        s = df[col].astype(str).replace('', np.nan).dropna()
        if len(s) == 0:
            continue
        vc = s.value_counts()
        total = vc.sum()
        top1_count = vc.iloc[0] if len(vc) else 0
        top1_pct = round(100 * top1_count / total, 1) if total else 0
        n_unique = len(vc)
        if top1_pct >= dominance_threshold_pct:
            top_val = str(vc.index[0])[:40] + ('…' if len(str(vc.index[0])) > 40 else '')
            findings.append({
                "category": "dominance",
                "column": col,
                "text": f"The column '{col}' is mostly one value: '{top_val}' ({top1_pct}% of rows). There are only {n_unique} different values in total.",
                "metric_value": top1_pct,
                "metric_label": "top value share (%)"
            })
    return findings


def discover_trends(df, date_col_candidates, num_cols):
    """If a time column exists, compute simple trend (e.g. mean over time buckets) and state increase/decrease."""
    findings = []
    time_col = None
    for col in date_col_candidates:
        if is_likely_date_column(df[col]):
            time_col = col
            break
    if time_col is None or not num_cols:
        return findings
    try:
        df_work = df[[time_col] + num_cols].copy()
        df_work[time_col] = pd.to_datetime(df_work[time_col], errors='coerce')
        df_work = df_work.dropna(subset=[time_col])
        if len(df_work) < 10:
            return findings
        df_work = df_work.sort_values(time_col)
        # Simple split: first half vs second half
        mid = len(df_work) // 2
        first_half = df_work.iloc[:mid]
        second_half = df_work.iloc[mid:]
        for num_col in num_cols:
            n1 = pd.to_numeric(first_half[num_col], errors='coerce').mean()
            n2 = pd.to_numeric(second_half[num_col], errors='coerce').mean()
            if pd.isna(n1) or pd.isna(n2):
                continue
            pct_change = 100 * (n2 - n1) / abs(n1) if n1 != 0 else 0
            if abs(pct_change) < 5:
                continue
            direction = "increased" if pct_change > 0 else "decreased"
            findings.append({
                "category": "trend",
                "column": num_col,
                "time_column": time_col,
                "text": f"Over time (using the column '{time_col}'), '{num_col}' {direction} by about {abs(round(pct_change, 1))}% when comparing the first half of the period to the second half.",
                "metric_value": round(pct_change, 1),
                "metric_label": "approximate % change"
            })
    except Exception:
        pass
    return findings


def discover_anomalies(df, num_cols, z_threshold=2.5, tail_pct=2):
    """Extreme values: values beyond z_threshold std, or in top/bottom tail_pct%."""
    findings = []
    for col in num_cols:
        vals = pd.to_numeric(df[col], errors='coerce').dropna()
        if len(vals) < 10:
            continue
        mean_val = vals.mean()
        std_val = vals.std()
        if std_val == 0:
            continue
        z = (vals - mean_val) / std_val
        extreme_count = (z.abs() > z_threshold).sum()
        if extreme_count > 0:
            pct = round(100 * extreme_count / len(vals), 1)
            findings.append({
                "category": "anomaly",
                "column": col,
                "text": f"The column '{col}' has {extreme_count} values ({pct}% of rows) that are far from the average. These unusual values are worth checking.",
                "metric_value": extreme_count,
                "metric_label": "outlier count"
            })
    return findings


def discover_insights(df):
    """Orchestrate insight discovery. Returns list of key findings (plain-English)."""
    key_findings = []
    num_cols = get_numeric_columns(df)
    cat_cols = get_categorical_columns(df)
    date_candidates = [c for c in df.columns if re.search(r'date|time|year|month|day', c, re.I)] or list(df.columns)

    key_findings.extend(discover_value_concentration(df, cat_cols))
    key_findings.extend(discover_patterns(df, cat_cols))
    key_findings.extend(discover_trends(df, date_candidates, num_cols))
    key_findings.extend(discover_anomalies(df, num_cols))

    # Summary finding
    key_findings.insert(0, {
        "category": "summary",
        "column": None,
        "text": f"Your data has {len(df)} rows and {len(df.columns)} columns. We looked at {len(num_cols)} number columns and {len(cat_cols)} category or text columns for insights, trends, and risks.",
        "metric_value": len(df),
        "metric_label": "row count"
    })
    return key_findings


# --- Risk Evaluation (rule-based) ---

RISK_TYPES = ("business", "data", "operational")
SEVERITIES = ("low", "medium", "high")


def evaluate_dependency_risk(df, cat_cols, high_pct=40, critical_pct=60):
    """High dependency on a single value → Business Risk."""
    risks = []
    for col in cat_cols:
        s = df[col].astype(str).replace('', np.nan).dropna()
        if len(s) == 0:
            continue
        vc = s.value_counts()
        total = vc.sum()
        top1_pct = 100 * vc.iloc[0] / total if total else 0
        if top1_pct >= critical_pct:
            severity = "high"
            risk_type = "business"
        elif top1_pct >= high_pct:
            severity = "medium"
            risk_type = "business"
        else:
            continue
        top_val = str(vc.index[0])[:40]
        risks.append({
            "type": risk_type,
            "severity": severity,
            "title": f"High dependency on a single value in '{col}'",
            "description": f"One value ('{top_val}') makes up {round(top1_pct, 1)}% of all rows. Relying too much on one type of value can be risky if things change.",
            "column": col,
            "metric_value": round(top1_pct, 1),
            "metric_label": "top value share (%)"
        })
    return risks


def evaluate_diversity_risk(df, cat_cols, low_unique_threshold=3, very_low=1):
    """Low diversity in key columns → Business / Operational Risk."""
    risks = []
    for col in cat_cols:
        n_unique = df[col].nunique()
        total = len(df[col].dropna())
        if total == 0:
            continue
        if n_unique <= very_low:
            severity = "high"
            risk_type = "operational"
        elif n_unique <= low_unique_threshold:
            severity = "medium"
            risk_type = "business"
        else:
            continue
        risks.append({
            "type": risk_type,
            "severity": severity,
            "title": f"Low diversity in '{col}'",
            "description": f"This column has only {n_unique} different value(s). Having few options can limit what you can do or show that data is concentrated in one place.",
            "column": col,
            "metric_value": n_unique,
            "metric_label": "unique count"
        })
    return risks


def evaluate_data_quality_risk(df, missing_pct_threshold=5, empty_col_threshold=0.5):
    """Remaining data quality weaknesses → Data Risk."""
    risks = []
    total_rows = len(df)
    if total_rows == 0:
        return risks
    for col in df.columns:
        missing = df[col].isna().sum()
        empty_str = (df[col].astype(str).str.strip() == '').sum()
        effective_missing = missing + empty_str
        pct = 100 * effective_missing / total_rows
        if pct >= missing_pct_threshold and pct < 100:
            severity = "high" if pct > 20 else "medium"
            risks.append({
                "type": "data",
                "severity": severity,
                "title": f"Missing or empty values in '{col}'",
                "description": f"{round(pct, 1)}% of values are missing or empty. This can make results less reliable when you use this column for decisions.",
                "column": col,
                "metric_value": round(pct, 1),
                "metric_label": "missing %"
            })
    return risks


def evaluate_risks(df, key_findings):
    """Orchestrate risk evaluation. Returns list of risks with type, severity, title, description."""
    risks = []
    cat_cols = get_categorical_columns(df)
    risks.extend(evaluate_dependency_risk(df, cat_cols))
    risks.extend(evaluate_diversity_risk(df, cat_cols))
    risks.extend(evaluate_data_quality_risk(df))
    return risks


# --- Strategy Recommendation (rule-based) ---

def recommend_strategies(key_findings, risks):
    """Generate actionable strategies from insights and risks. Each: reason, priority."""
    strategies = []
    seen_reasons = set()

    # Dependency → diversify
    dep_risks = [r for r in risks if "dependency" in r.get("title", "").lower()]
    if dep_risks:
        reason = "Too much of your data is in one place (e.g. one city, product, or category). Spreading things out can lower the risk if that one area has problems."
        if reason not in seen_reasons:
            seen_reasons.add(reason)
            strategies.append({
                "title": "Spread out your data",
                "reason": reason,
                "priority": "immediate" if any(r.get("severity") == "high" for r in dep_risks) else "short-term",
                "category": "market"
            })

    # Low diversity → expand options
    div_risks = [r for r in risks if "diversity" in r.get("title", "").lower()]
    if div_risks:
        reason = "Some columns have very few different values. Adding more options or sources can make your data and decisions stronger."
        if reason not in seen_reasons:
            seen_reasons.add(reason)
            strategies.append({
                "title": "Add more options or sources",
                "reason": reason,
                "priority": "short-term",
                "category": "operational"
            })

    # Data quality → governance
    data_risks = [r for r in risks if r.get("type") == "data"]
    if data_risks:
        reason = "Missing or inconsistent values can lead to wrong conclusions. Checking and cleaning data before use helps you make better decisions."
        if reason not in seen_reasons:
            seen_reasons.add(reason)
            strategies.append({
                "title": "Improve how you collect and check data",
                "reason": reason,
                "priority": "immediate" if any(r.get("severity") == "high" for r in data_risks) else "short-term",
                "category": "data_governance"
            })

    # Anomalies → review process
    anomaly_findings = [f for f in key_findings if f.get("category") == "anomaly"]
    if anomaly_findings:
        reason = "Some values are very different from the rest. They may be mistakes, special cases, or important. Having a simple process to check them keeps things consistent."
        if reason not in seen_reasons:
            seen_reasons.add(reason)
            strategies.append({
                "title": "Set up a simple way to check unusual values",
                "reason": reason,
                "priority": "short-term",
                "category": "process"
            })

    # Trends → monitor and plan
    trend_findings = [f for f in key_findings if f.get("category") == "trend"]
    if trend_findings:
        reason = "Your data shows things changing over time. Checking it regularly and thinking ahead helps you stay on track."
        if reason not in seen_reasons:
            seen_reasons.add(reason)
            strategies.append({
                "title": "Watch trends and update your plans",
                "reason": reason,
                "priority": "long-term",
                "category": "planning"
            })

    # Default: no major risks
    if not strategies:
        strategies.append({
            "title": "Keep checking your data from time to time",
            "reason": "No big risks or concentration issues were found. Keep reviewing your data and main numbers so your decisions stay well informed.",
            "priority": "long-term",
            "category": "maintenance"
        })

    return strategies


# --- Main ---

def run_engine(df):
    """Run full pipeline: insights → risks → strategies. Returns dict for API."""
    key_findings = discover_insights(df)
    risks = evaluate_risks(df, key_findings)
    strategies = recommend_strategies(key_findings, risks)
    return {
        "key_findings": key_findings,
        "risks": risks,
        "strategies": strategies
    }


def main():
    try:
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")

        df = load_file(file_path, file_type)
        result = run_engine(df)
        result["success"] = True
        result["total_rows"] = len(df)
        result["total_columns"] = len(df.columns)
        result = to_native(result)
        print(json.dumps(result, default=to_native))
    except Exception as e:
        out = {
            "success": False,
            "error": str(e),
            "error_type": type(e).__name__,
            "key_findings": [],
            "risks": [],
            "strategies": []
        }
        print(json.dumps(out))
        sys.exit(1)


if __name__ == "__main__":
    main()
