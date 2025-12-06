import sys, json, pandas as pd, numpy as np

payload = json.loads(sys.argv[1])
file_path = payload["file_path"]
file_type = payload["file_type"]


df = pd.read_csv(file_path) if file_type == "csv" else pd.DataFrame()

column_stats = {}

for col in df.columns:
    col_data = df[col]
    empty_count = col_data.isna().sum() + (col_data == '').sum()
    non_null = col_data.dropna()

    if len(non_null) == 0:
        detected_type = "empty"
    elif pd.api.types.is_numeric_dtype(col_data):
        detected_type = "number"
    elif pd.api.types.is_datetime64_any_dtype(col_data):
        detected_type = "date"
    else:
        numeric_ratio = pd.to_numeric(non_null, errors='coerce').notna().mean()
        detected_type = "text-number" if numeric_ratio > 0.8 else "text"

    column_stats[col] = {
        "empty_count": int(empty_count),
        "empty_percentage": round((empty_count / len(df)) * 100, 1) if len(df) else 0,
        "duplicate_count": int(col_data.duplicated().sum()),
        "data_type": detected_type,
        "unique_values": int(col_data.nunique())
    }


df = df.replace({np.nan: None})

result = {
    "rows": len(df),
    "columns": len(df.columns),
    "columns_list": df.columns.tolist(),
    "column_stats": column_stats,
    "total_duplicate_rows": int(df.duplicated().sum()),
    "data": df.to_dict(orient="records")
}

print(json.dumps(result))
