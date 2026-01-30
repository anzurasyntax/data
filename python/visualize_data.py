"""
Generate chart-ready data for visualization dashboard.
Returns histogram data for numeric columns, value counts for categorical, and optional correlation matrix.
"""
import sys
import json
import os
import pandas as pd
import numpy as np


def to_native(obj):
    """Convert numpy/pandas types to native Python types for JSON serialization."""
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
    allowed_types = ['csv', 'txt', 'xml', 'xlsx']
    if file_type not in allowed_types:
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


def get_column_type(col_data):
    """Determine if column is numeric or categorical."""
    numeric_values = pd.to_numeric(col_data, errors='coerce')
    non_null = col_data.replace('', np.nan).dropna()
    if len(non_null) == 0:
        return "empty"
    numeric_ratio = numeric_values.notna().mean()
    if numeric_ratio > 0.8:
        return "numeric"
    if numeric_ratio > 0:
        return "numeric"  # treat text-number as numeric for charts
    return "categorical"


def histogram_data(col_data, bins=15):
    """Compute histogram bins and counts for a numeric column. Bins = min(bins, data count) to keep math correct."""
    numeric = pd.to_numeric(col_data, errors='coerce').dropna()
    if len(numeric) == 0:
        return {"labels": [], "values": []}
    try:
        n = len(numeric)
        n_bins = min(bins, max(5, n // 5), n)  # at least 5 bins, never more than data points
        if n_bins < 1:
            n_bins = 1
        counts, bin_edges = np.histogram(numeric, bins=int(n_bins))
        labels = [round(float((bin_edges[i] + bin_edges[i + 1]) / 2), 2) for i in range(len(bin_edges) - 1)]
        values = [int(c) for c in counts]
        return {"labels": labels, "values": values}
    except Exception:
        return {"labels": [], "values": []}


def value_counts_data(col_data, top_n=15):
    """Top N value counts for categorical column."""
    str_col = col_data.astype(str).replace('', np.nan).dropna()
    if len(str_col) == 0:
        return {"labels": [], "values": []}
    vc = str_col.value_counts().head(top_n)
    labels = [str(x) for x in vc.index.tolist()]
    values = [int(x) for x in vc.values.tolist()]
    return {"labels": labels, "values": values}


def generate_insights(df, columns_data, correlation_matrix, numeric_columns):
    """Generate smart, informative insights from the data (AI-style auto-analysis)."""
    insights = []
    n_rows = len(df)
    n_cols = len(df.columns)
    numeric_count = sum(1 for c in columns_data if c.get("type") == "numeric")
    cat_count = n_cols - numeric_count

    # Dataset overview (simple English)
    insights.append({
        "type": "overview",
        "icon": "dataset",
        "title": "Your data at a glance",
        "text": f"You have {n_rows:,} rows and {n_cols} columns. {numeric_count} columns are numbers, {cat_count} are categories or text.",
        "highlight": f"{n_rows:,} rows"
    })

    # Numeric column insights
    for c in columns_data:
        if c.get("type") != "numeric" or not c.get("stats"):
            continue
        name = c["name"]
        stats = c["stats"]
        mean_val = stats.get("mean")
        min_val = stats.get("min")
        max_val = stats.get("max")
        if mean_val is None:
            continue
        spread = (max_val - min_val) if (min_val is not None and max_val is not None) else 0
        # Distribution hint (simple English)
        if spread == 0:
            dist_hint = "All values are the same."
        elif abs(mean_val) > 1e6:
            dist_hint = "Numbers are very large. For charts, you may want to use a different scale."
        else:
            dist_hint = f"Values range from {min_val} to {max_val}. Average is {mean_val}."
        insights.append({
            "type": "numeric",
            "icon": "trend",
            "column": name,
            "title": f"Column: {name}",
            "text": dist_hint,
            "highlight": f"Average = {mean_val}"
        })

    # Categorical: top value and diversity
    for c in columns_data:
        if c.get("type") not in ("categorical", "empty"):
            continue
        vc = c.get("value_counts")
        if not vc or not vc.get("values"):
            continue
        name = c["name"]
        top_label = vc["labels"][0] if vc["labels"] else ""
        top_count = vc["values"][0] if vc["values"] else 0
        total = sum(vc["values"])
        pct = round(100 * top_count / total, 1) if total else 0
        n_unique = len(vc["labels"])
        if n_unique <= 5:
            diversity = "Low diversity"
        elif n_unique <= 15:
            diversity = "Moderate diversity"
        else:
            diversity = "High diversity"
        top_short = str(top_label)[:30] + ("..." if len(str(top_label)) > 30 else "")
        insights.append({
            "type": "categorical",
            "icon": "category",
            "column": name,
            "title": f"Column: {name}",
            "text": f"The most common value is '{top_short}' ({pct}% of rows). {diversity} with {n_unique} different values.",
            "highlight": f"{pct}%"
        })

    # Correlation highlights (strong pairs)
    if correlation_matrix and len(correlation_matrix.get("labels", [])) >= 2:
        labels = correlation_matrix["labels"]
        matrix = correlation_matrix["matrix"]
        best_pos = (0, 0, -1)
        best_neg = (0, 0, 1)
        for i in range(len(matrix)):
            for j in range(len(matrix[i])):
                if i == j:
                    continue
                v = matrix[i][j]
                if v > best_pos[2]:
                    best_pos = (i, j, v)
                if v < best_neg[2]:
                    best_neg = (i, j, v)
        if best_pos[2] > 0.5:
            insights.append({
                "type": "correlation",
                "icon": "link",
                "title": "Strong positive link between two columns",
                "text": f"'{labels[best_pos[0]]}' and '{labels[best_pos[1]]}' move together (score {best_pos[2]:.2f}).",
                "highlight": f"Score = {best_pos[2]:.2f}"
            })
        if best_neg[2] < -0.5:
            insights.append({
                "type": "correlation",
                "icon": "link",
                "title": "Strong negative link between two columns",
                "text": f"'{labels[best_neg[0]]}' and '{labels[best_neg[1]]}' move in opposite ways (score {best_neg[2]:.2f}).",
                "highlight": f"Score = {best_neg[2]:.2f}"
            })

    # Recommended chart types per column (for frontend)
    for i, c in enumerate(columns_data):
        rec = "bar"
        if c.get("type") == "numeric":
            rec = "bar"  # histogram default
        elif c.get("type") == "categorical":
            vc = c.get("value_counts") or {}
            n = len(vc.get("labels") or [])
            rec = "pie" if n <= 7 else "bar"  # pie for few categories
        c["recommended_chart"] = rec

    return insights


def main():
    try:
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")

        df = load_file(file_path, file_type)
        columns_data = []
        numeric_columns = []

        for col in df.columns:
            col_data = df[col]
            col_type = get_column_type(col_data)
            entry = {"name": col, "type": col_type}

            if col_type == "numeric":
                numeric_columns.append(col)
                entry["histogram"] = histogram_data(col_data)
                numeric_vals = pd.to_numeric(col_data, errors='coerce').dropna()
                if len(numeric_vals) > 0:
                    entry["stats"] = {
                        "min": round(float(numeric_vals.min()), 2),
                        "max": round(float(numeric_vals.max()), 2),
                        "mean": round(float(numeric_vals.mean()), 2),
                        "median": round(float(numeric_vals.median()), 2),
                    }
            elif col_type == "categorical":
                entry["value_counts"] = value_counts_data(col_data)
            else:
                entry["value_counts"] = value_counts_data(col_data)

            columns_data.append(entry)

        # Correlation matrix for numeric columns (if at least 2)
        correlation_matrix = None
        if len(numeric_columns) >= 2:
            try:
                numeric_df = df[numeric_columns].apply(pd.to_numeric, errors='coerce')
                corr = numeric_df.corr()
                correlation_matrix = {
                    "labels": numeric_columns,
                    "matrix": [list(map(lambda x: round(float(x), 2), row)) for row in corr.values.tolist()]
                }
            except Exception:
                pass

        insights = generate_insights(df, columns_data, correlation_matrix, numeric_columns)

        result = {
            "success": True,
            "total_rows": len(df),
            "total_columns": len(df.columns),
            "columns": to_native(columns_data),
            "correlation_matrix": to_native(correlation_matrix),
            "insights": to_native(insights),
        }
        print(json.dumps(result, default=to_native))
    except Exception as e:
        error_result = {
            "success": False,
            "error": str(e),
            "error_type": type(e).__name__
        }
        print(json.dumps(error_result))
        sys.exit(1)


if __name__ == "__main__":
    main()
