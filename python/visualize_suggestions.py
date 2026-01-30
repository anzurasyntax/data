"""
Rule-based suggestions for visualization (no AI). Returns columns, suggested chart types,
suggested correlation columns, and suggested regression pairs. User selects from these, then we build.
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
    numeric_ratio = numeric_values.notna().mean()
    if numeric_ratio > 0.8:
        return "numeric"
    if numeric_ratio > 0:
        return "numeric"
    return "categorical"


def main():
    try:
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")

        df = load_file(file_path, file_type)
        columns = []
        numeric_cols = []
        categorical_cols = []
        suggested_charts = []
        suggested_correlation_columns = []
        suggested_regression_pairs = []

        for col in df.columns:
            col_type = get_column_type(df[col])
            columns.append({"name": col, "type": col_type})
            if col_type == "numeric":
                numeric_cols.append(col)
            elif col_type == "categorical":
                categorical_cols.append(col)

        # Suggest chart per column (rule-based)
        for c in columns:
            name = c["name"]
            t = c["type"]
            if t == "numeric":
                suggested_charts.append({"type": "bar", "chart_label": "Bar (distribution)", "column": name, "description": f"Distribution of {name}"})
                suggested_charts.append({"type": "line", "chart_label": "Line", "column": name, "description": f"Trend of {name}"})
            elif t == "categorical":
                n_unique = df[name].nunique()
                suggested_charts.append({"type": "bar", "chart_label": "Bar", "column": name, "description": f"Counts for {name}"})
                if n_unique <= 8:
                    suggested_charts.append({"type": "pie", "chart_label": "Pie", "column": name, "description": f"Share of {name}"})

        # Suggest scatter for numeric pairs (top pairs by correlation strength)
        if len(numeric_cols) >= 2:
            try:
                num_df = df[numeric_cols].apply(pd.to_numeric, errors='coerce').dropna(how='all')
                if len(num_df) >= 5:
                    corr = num_df.corr()
                    pairs = []
                    for i in range(len(numeric_cols)):
                        for j in range(i + 1, len(numeric_cols)):
                            r = corr.iloc[i, j]
                            if pd.notna(r):
                                pairs.append((numeric_cols[i], numeric_cols[j], abs(float(r))))
                    pairs.sort(key=lambda x: x[2], reverse=True)
                    for x_col, y_col, _ in pairs[:5]:
                        suggested_charts.append({"type": "scatter", "chart_label": "Scatter", "x_column": x_col, "y_column": y_col, "description": f"{x_col} vs {y_col}"})
                        suggested_regression_pairs.append({"x_column": x_col, "y_column": y_col, "description": f"Regression: {y_col} on {x_col}"})
            except Exception:
                pass

        # Correlation: suggest all numeric columns
        suggested_correlation_columns = list(numeric_cols) if len(numeric_cols) >= 2 else []

        result = {
            "success": True,
            "total_rows": len(df),
            "total_columns": len(df.columns),
            "columns": to_native(columns),
            "suggested_charts": to_native(suggested_charts),
            "suggested_correlation_columns": to_native(suggested_correlation_columns),
            "suggested_regression_pairs": to_native(suggested_regression_pairs[:10]),
        }
        print(json.dumps(result, default=to_native))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e), "error_type": type(e).__name__}))
        sys.exit(1)


if __name__ == "__main__":
    main()
