import sys
import json
import pandas as pd
import numpy as np

payload = json.loads(sys.argv[1])

file_type = payload['file_type']
file_path = payload['file_path']

result = {
    "file_type": file_type,
    "file_path": file_path,
    "rows": None,
    "columns": None,
    "columns_info": [],
    "data": []
}

def to_python_value(val):
    """Convert NumPy types to native Python types for JSON serialization."""
    if isinstance(val, (np.int64, np.int32)):
        return int(val)
    if isinstance(val, (np.float64, np.float32)):
        return float(val)
    if pd.isna(val):
        return None
    return val

try:
    # File reading
    if file_type == "csv":
        df = pd.read_csv(file_path)
    elif file_type == "json":
        df = pd.read_json(file_path)
    elif file_type == "txt":
        df = pd.read_csv(file_path, sep="\t")
    else:
        result["error"] = "Unsupported file format"
        print(json.dumps(result))
        sys.exit(0)

    result["rows"] = len(df)
    result["columns"] = len(df.columns)

    columns_info = []

    for col in df.columns:
        series = df[col]
        dtype = str(series.dtype)

        col_info = {
            "name": col,
            "data_type": dtype,
            "null_count": int(series.isnull().sum()),
            "stats": {}
        }

        # ---- NUMERIC COLUMNS ----
        if pd.api.types.is_numeric_dtype(series):
            desc = series.describe()
            allowed_stats = ["count", "mean", "std", "min", "25%", "50%", "75%", "max"]
            for stat in allowed_stats:
                if stat in desc:
                    col_info["stats"][stat] = to_python_value(desc[stat])

        elif pd.api.types.is_object_dtype(series):
            desc = series.describe()
            allowed_stats = ["count", "unique", "top", "freq"]
            for stat in allowed_stats:
                if stat in desc:
                    col_info["stats"][stat] = to_python_value(desc[stat])

        else:
            desc = series.describe(include="all")
            for stat_name, stat_value in desc.items():
                col_info["stats"][stat_name] = to_python_value(stat_value)

        columns_info.append(col_info)

    result["columns_info"] = columns_info

    result["data"] = df.where(pd.notnull(df), None).to_dict(orient="records")

except Exception as e:
    result["error"] = str(e)

print(json.dumps(result, default=str))
