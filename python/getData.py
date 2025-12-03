import sys
import json
import pandas as pd

# Read JSON input
payload = json.loads(sys.argv[1])

file_type = payload['file_type']
file_path = payload['file_path']

result = {
    "file_type": file_type,
    "file_path": file_path,
    "rows": None,
    "columns": None,
    "columns_list": None
}

try:
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
    result["columns_list"] = df.columns.tolist()

except Exception as e:
    result["error"] = str(e)

print(json.dumps(result))
