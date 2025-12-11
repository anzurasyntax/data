import sys, json, pandas as pd, numpy as np

def to_native(obj):
    if isinstance(obj, (np.integer,)):
        return int(obj)
    if isinstance(obj, (np.floating,)):
        return float(obj)
    if isinstance(obj, (pd.Timestamp,)):
        return obj.strftime("%Y-%m-%d %H:%M:%S")
    return obj

payload = json.loads(sys.argv[1])
file_path = payload["file_path"]
file_type = payload["file_type"]

df = pd.read_csv(file_path) if file_type == "csv" else pd.DataFrame()

column_stats = {}
outlier_map = {}

for col in df.columns:
    col_data = df[col]

    empty_count = col_data.isna().sum() + (col_data.astype(str) == '').sum()

    actual_non_empty = col_data.replace('', np.nan).dropna()

    numeric_values = pd.to_numeric(col_data, errors='coerce')
    non_null_numeric = numeric_values.dropna()

    if len(actual_non_empty) == 0:
        detected_type = "empty"
    else:
        numeric_ratio = numeric_values.notna().mean()

        if numeric_ratio > 0.8:
            detected_type = "number"
        elif numeric_ratio > 0:
            detected_type = "text-number"
        else:
            detected_type = "text"

    stats = {}
    outlier_count = 0

    if detected_type in ["number", "text-number"] and len(non_null_numeric) > 0:
        q1 = non_null_numeric.quantile(0.25)
        q3 = non_null_numeric.quantile(0.75)
        iqr = q3 - q1
        lower = q1 - 1.5 * iqr
        upper = q3 + 1.5 * iqr

        outlier_mask = (numeric_values < lower) | (numeric_values > upper)
        outlier_indexes = outlier_mask[outlier_mask].index.tolist()
        outlier_count = len(outlier_indexes)


        for idx in outlier_indexes:
            if str(idx) not in outlier_map:
                outlier_map[str(idx)] = {}
            outlier_map[str(idx)][col] = True

        stats = {
            "min": round(non_null_numeric.min(), 2),
            "max": round(non_null_numeric.max(), 2),
            "mean": round(non_null_numeric.mean(), 2),
            "median": round(non_null_numeric.median(), 2),
            "std": round(non_null_numeric.std(), 2)
        }

    column_stats[col] = {
        "empty_count": int(empty_count),
        "empty_percentage": round((empty_count / len(df)) * 100, 1) if len(df) else 0,
        "duplicate_count": int(col_data.duplicated().sum()),
        "data_type": detected_type,
        "unique_values": int(col_data.nunique()),
        "outlier_count": int(outlier_count),
        "stats": stats
    }

df = df.replace({np.nan: None})

result = {
    "rows": len(df),
    "columns": len(df.columns),
    "columns_list": df.columns.tolist(),
    "column_stats": column_stats,
    "outlier_map": outlier_map,
    "total_duplicate_rows": int(df.duplicated().sum()),
    "data": df.to_dict(orient="records")
}

print(json.dumps(result, default=to_native))
