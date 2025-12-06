import sys, json, pandas as pd
import numpy as np

payload = json.loads(sys.argv[1])
file_path = payload["file_path"]
file_type = payload["file_type"]

if file_type == "csv":
    df = pd.read_csv(file_path)
else:
    df = pd.DataFrame()

# Calculate column statistics
column_stats = {}
for col in df.columns:
    # Count empty/null values
    empty_count = df[col].isna().sum() + (df[col] == '').sum()

    # Detect data type
    non_null_vals = df[col].dropna()
    if len(non_null_vals) == 0:
        detected_type = "empty"
    elif pd.api.types.is_numeric_dtype(df[col]):
        detected_type = "number"
    elif pd.api.types.is_datetime64_any_dtype(df[col]):
        detected_type = "date"
    else:
        # Check if text column contains mostly numbers
        try:
            numeric_count = pd.to_numeric(non_null_vals, errors='coerce').notna().sum()
            if numeric_count / len(non_null_vals) > 0.8:
                detected_type = "text-number"
            else:
                detected_type = "text"
        except:
            detected_type = "text"

    # Count duplicates in this column
    duplicate_count = df[col].duplicated().sum()

    column_stats[col] = {
        "empty_count": int(empty_count),
        "empty_percentage": round((empty_count / len(df)) * 100, 1),
        "duplicate_count": int(duplicate_count),
        "data_type": detected_type,
        "unique_values": int(df[col].nunique())
    }

# Convert NaN to None to make JSON valid
df = df.replace({np.nan: None})

# Count total duplicate rows
total_duplicates = df.duplicated().sum()

result = {
    "rows": len(df),
    "columns": len(df.columns),
    "columns_list": list(df.columns),
    "column_stats": column_stats,
    "total_duplicate_rows": int(total_duplicates),
    "data": df.to_dict(orient="records")
}

print(json.dumps(result))
