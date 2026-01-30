"""
Build visualization data from user selections. Correct calculations for histogram,
value counts, scatter, correlation matrix, and simple linear regression.
"""
import sys
import json
import os
import pandas as pd
import numpy as np


def to_native(obj):
    if obj is None:
        return None
    if isinstance(obj, (np.integer,)):
        return int(obj)
    if isinstance(obj, (np.floating,)):
        return float(obj)
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


def get_column_type(col_data):
    numeric_values = pd.to_numeric(col_data, errors='coerce')
    non_null = col_data.replace('', np.nan).dropna()
    if len(non_null) == 0:
        return "empty"
    if numeric_values.notna().mean() > 0.8:
        return "numeric"
    if numeric_values.notna().mean() > 0:
        return "numeric"
    return "categorical"


def histogram_data(col_data, bins=15):
    numeric = pd.to_numeric(col_data, errors='coerce').dropna()
    if len(numeric) == 0:
        return {"labels": [], "values": []}
    n = len(numeric)
    n_bins = min(bins, max(5, n // 5), n)
    if n_bins < 1:
        n_bins = 1
    counts, bin_edges = np.histogram(numeric, bins=int(n_bins))
    labels = [round(float((bin_edges[i] + bin_edges[i + 1]) / 2), 2) for i in range(len(bin_edges) - 1)]
    values = [int(c) for c in counts]
    return {"labels": labels, "values": values}


def value_counts_data(col_data, top_n=15):
    str_col = col_data.astype(str).replace('', np.nan).dropna()
    if len(str_col) == 0:
        return {"labels": [], "values": []}
    vc = str_col.value_counts().head(top_n)
    labels = [str(x) for x in vc.index.tolist()]
    values = [int(x) for x in vc.values.tolist()]
    return {"labels": labels, "values": values}


def simple_linear_regression(x_vals, y_vals):
    """Compute slope, intercept, R^2, and line points. All correct formulas."""
    x = np.asarray(x_vals, dtype=float)
    y = np.asarray(y_vals, dtype=float)
    mask = np.isfinite(x) & np.isfinite(y)
    x = x[mask]
    y = y[mask]
    n = len(x)
    if n < 2:
        return None
    x_mean = np.mean(x)
    y_mean = np.mean(y)
    ss_xx = np.sum((x - x_mean) ** 2)
    ss_yy = np.sum((y - y_mean) ** 2)
    ss_xy = np.sum((x - x_mean) * (y - y_mean))
    if ss_xx == 0:
        return None
    slope = ss_xy / ss_xx
    intercept = y_mean - slope * x_mean
    ss_res = np.sum((y - (intercept + slope * x)) ** 2)
    r_squared = 1 - (ss_res / ss_yy) if ss_yy != 0 else 0
    x_min = float(np.min(x))
    x_max = float(np.max(x))
    x_line = [x_min, x_max]
    y_line = [intercept + slope * x_min, intercept + slope * x_max]
    return {
        "slope": round(float(slope), 4),
        "intercept": round(float(intercept), 4),
        "r_squared": round(float(r_squared), 4),
        "line_x": [round(float(v), 4) for v in x_line],
        "line_y": [round(float(v), 4) for v in y_line],
        "n_points": n,
    }


def main():
    try:
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        charts = payload.get("charts", [])
        correlation_columns = payload.get("correlation_columns", [])
        regression = payload.get("regression", {})
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")

        df = load_file(file_path, file_type)
        built_charts = []
        correlation_result = None
        regression_result = None

        for i, ch in enumerate(charts):
            ctype = (ch.get("type") or ch.get("chart_type") or "bar").lower()
            column = ch.get("column")
            x_col = ch.get("x_column")
            y_col = ch.get("y_column")
            entry = {"id": i, "type": ctype}

            if ctype in ("bar", "line", "pie", "doughnut"):
                if not column or column not in df.columns:
                    continue
                col_data = df[column]
                col_type = get_column_type(col_data)
                if col_type == "numeric":
                    hist = histogram_data(col_data)
                    entry["data"] = {"labels": hist["labels"], "values": hist["values"], "column": column}
                else:
                    vc = value_counts_data(col_data)
                    entry["data"] = {"labels": vc["labels"], "values": vc["values"], "column": column}
                built_charts.append(entry)

            elif ctype == "scatter":
                if not x_col or not y_col or x_col not in df.columns or y_col not in df.columns:
                    continue
                x_vals = pd.to_numeric(df[x_col], errors='coerce').dropna()
                y_vals = pd.to_numeric(df[y_col], errors='coerce').dropna()
                common_idx = x_vals.index.intersection(y_vals.index)
                x_vals = x_vals.loc[common_idx].tolist()
                y_vals = y_vals.loc[common_idx].tolist()
                entry["data"] = {"x": to_native(x_vals), "y": to_native(y_vals), "x_column": x_col, "y_column": y_col}
                built_charts.append(entry)

        if correlation_columns and len(correlation_columns) >= 2:
            valid = [c for c in correlation_columns if c in df.columns]
            if len(valid) >= 2:
                try:
                    num_df = df[valid].apply(pd.to_numeric, errors='coerce')
                    corr = num_df.corr()
                    correlation_result = {
                        "labels": valid,
                        "matrix": [list(map(lambda x: round(float(x), 4), row)) for row in corr.values.tolist()]
                    }
                except Exception:
                    pass

        x_col = regression.get("x_column") if isinstance(regression, dict) else None
        y_col = regression.get("y_column") if isinstance(regression, dict) else None
        if x_col and y_col and x_col in df.columns and y_col in df.columns:
            x_vals = pd.to_numeric(df[x_col], errors='coerce').dropna()
            y_vals = pd.to_numeric(df[y_col], errors='coerce').dropna()
            common_idx = x_vals.index.intersection(y_vals.index)
            x_vals = x_vals.loc[common_idx].values
            y_vals = y_vals.loc[common_idx].values
            reg = simple_linear_regression(x_vals, y_vals)
            if reg:
                regression_result = {
                    "x_column": x_col,
                    "y_column": y_col,
                    "slope": reg["slope"],
                    "intercept": reg["intercept"],
                    "r_squared": reg["r_squared"],
                    "line_x": reg["line_x"],
                    "line_y": reg["line_y"],
                    "n_points": reg["n_points"],
                    "x_points": to_native(np.asarray(x_vals).tolist()),
                    "y_points": to_native(np.asarray(y_vals).tolist()),
                }

        result = {
            "success": True,
            "total_rows": len(df),
            "charts": to_native(built_charts),
            "correlation": to_native(correlation_result),
            "regression": to_native(regression_result),
        }
        print(json.dumps(result, default=to_native))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e), "error_type": type(e).__name__}))
        sys.exit(1)


if __name__ == "__main__":
    main()
