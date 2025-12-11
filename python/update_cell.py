import sys, json, pandas as pd, numpy as np

def to_native(obj):
    if isinstance(obj, (np.integer,)):
        return int(obj)
    if isinstance(obj, (np.floating,)):
        return float(obj)
    if isinstance(obj, (pd.Timestamp,)):
        return obj.strftime("%Y-%m-%d %H:%M:%S")
    return obj

def calculate_column_stats(df, col):
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
    outlier_indexes = []
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

        stats = {
            "min": round(non_null_numeric.min(), 2),
            "max": round(non_null_numeric.max(), 2),
            "mean": round(non_null_numeric.mean(), 2),
            "median": round(non_null_numeric.median(), 2),
            "std": round(non_null_numeric.std(), 2)
        }

    return {
        "empty_count": int(empty_count),
        "empty_percentage": round((empty_count / len(df)) * 100, 1) if len(df) else 0,
        "duplicate_count": int(col_data.duplicated().sum()),
        "data_type": detected_type,
        "unique_values": int(col_data.nunique()),
        "outlier_count": int(outlier_count),
        "stats": stats
    }, outlier_indexes

payload = json.loads(sys.argv[1])
file_path = payload["file_path"]
file_type = payload["file_type"]
row_index = payload["row_index"]
column = payload["column"]
new_value = payload.get("value", "")

df = pd.read_csv(file_path) if file_type == "csv" else pd.DataFrame()

if new_value == "" or new_value is None:
    df.at[row_index, column] = np.nan
else:
    df.at[row_index, column] = new_value

if file_type == "csv":
    df.to_csv(file_path, index=False)

column_stats = {}
outlier_map = {}

for col in df.columns:
    col_stats, outlier_indexes = calculate_column_stats(df, col)
    column_stats[col] = col_stats

    for idx in outlier_indexes:
        if str(idx) not in outlier_map:
            outlier_map[str(idx)] = {}
        outlier_map[str(idx)][col] = True

updated_value = df.at[row_index, column]
if pd.isna(updated_value):
    updated_value = None

result = {
    "column_stats": column_stats,
    "total_duplicate_rows": int(df.duplicated().sum()),
    "outlier_map": outlier_map,
    "updated_value": updated_value
}

print(json.dumps(result, default=to_native))
