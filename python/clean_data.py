import sys
import json
import os
import pandas as pd
import numpy as np

def to_native(obj):
    """Convert numpy/pandas types to native Python types for JSON serialization."""
    if isinstance(obj, (np.integer,)):
        return int(obj)
    if isinstance(obj, (np.floating,)):
        return float(obj)
    if isinstance(obj, (pd.Timestamp,)):
        return obj.strftime("%Y-%m-%d %H:%M:%S")
    return obj

def load_file(file_path, file_type):
    """Load file based on type with proper error handling."""
    file_path = os.path.normpath(file_path)
    
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"File not found: {file_path}")
    
    allowed_types = ['csv', 'txt', 'xml', 'xlsx']
    if file_type not in allowed_types:
        raise ValueError(f"Unsupported file type: {file_type}")
    
    try:
        if file_type == 'csv':
            df = pd.read_csv(file_path)
        elif file_type == 'txt':
            try:
                df = pd.read_csv(file_path, sep=',')
            except:
                try:
                    df = pd.read_csv(file_path, sep='\t')
                except:
                    df = pd.read_csv(file_path, sep=r'\s+', engine='python')
        elif file_type == 'xml':
            df = pd.read_xml(file_path)
        elif file_type == 'xlsx':
            try:
                df = pd.read_excel(file_path, engine='openpyxl')
            except ImportError:
                raise ImportError("openpyxl library is required for Excel files")
            except Exception as e:
                raise Exception(f"Failed to read Excel file: {str(e)}")
        else:
            raise ValueError(f"Unsupported file type: {file_type}")
        
        return df
    except pd.errors.EmptyDataError:
        raise ValueError("The file is empty or contains no valid data")
    except pd.errors.ParserError as e:
        raise ValueError(f"Failed to parse file: {str(e)}")
    except Exception as e:
        raise Exception(f"Error reading file: {str(e)}")

def save_file(df, file_path, file_type):
    """Save DataFrame to file based on type."""
    file_path = os.path.normpath(file_path)
    
    try:
        if file_type == 'csv':
            df.to_csv(file_path, index=False)
        elif file_type == 'txt':
            df.to_csv(file_path, index=False, sep='\t')
        elif file_type == 'xml':
            df.to_xml(file_path, index=False)
        elif file_type == 'xlsx':
            try:
                df.to_excel(file_path, index=False, engine='openpyxl')
            except ImportError:
                raise ImportError("openpyxl library is required for Excel files")
            except Exception as e:
                raise Exception(f"Failed to write Excel file: {str(e)}")
        else:
            raise ValueError(f"Unsupported file type: {file_type}")
    except Exception as e:
        raise Exception(f"Error saving file: {str(e)}")

def impute_missing_values(df, column, method, value=None):
    """Impute missing values in a column using specified method."""
    if column not in df.columns:
        raise ValueError(f"Column '{column}' does not exist")
    
    col_data = df[column].copy()
    str_col = col_data.astype(str)
    # Include NaN, empty strings, and whitespace-only strings as missing
    # str_col.str.strip() == '' catches both empty strings and whitespace-only strings
    missing_mask = col_data.isna() | (str_col.str.strip() == '')
    
    if not missing_mask.any():
        return df  # No missing values
    
    if method == 'mean':
        numeric_values = pd.to_numeric(col_data, errors='coerce')
        impute_value = numeric_values.mean()
        df.loc[missing_mask, column] = impute_value
    elif method == 'median':
        numeric_values = pd.to_numeric(col_data, errors='coerce')
        impute_value = numeric_values.median()
        df.loc[missing_mask, column] = impute_value
    elif method == 'mode':
        mode_value = col_data.mode()
        impute_value = mode_value[0] if len(mode_value) > 0 else None
        if impute_value is not None:
            df.loc[missing_mask, column] = impute_value
    elif method == 'forward_fill':
        df[column] = df[column].fillna(method='ffill')
    elif method == 'backward_fill':
        df[column] = df[column].fillna(method='bfill')
    elif method == 'interpolate':
        numeric_values = pd.to_numeric(col_data, errors='coerce')
        df[column] = numeric_values.interpolate(method='linear')
        # Fill any remaining NaN with forward/backward fill
        df[column] = df[column].fillna(method='ffill').fillna(method='bfill')
    elif method == 'constant':
        if value is None:
            raise ValueError("Value is required for constant imputation")
        df.loc[missing_mask, column] = value
    elif method == 'remove_rows':
        df = df.dropna(subset=[column])
        df = df[df[column].astype(str) != '']
    elif method == 'remove_column':
        df = df.drop(columns=[column])
    else:
        raise ValueError(f"Unknown imputation method: {method}")
    
    return df

def handle_outliers(df, column, method, lower_percentile=None, upper_percentile=None):
    """Handle outliers in a column using specified method."""
    if column not in df.columns:
        raise ValueError(f"Column '{column}' does not exist")
    
    col_data = pd.to_numeric(df[column], errors='coerce')
    non_null = col_data.dropna()
    
    if len(non_null) == 0:
        return df
    
    # Calculate IQR
    q1 = non_null.quantile(0.25)
    q3 = non_null.quantile(0.75)
    iqr = q3 - q1
    
    if iqr > 0:
        lower = q1 - 1.5 * iqr
        upper = q3 + 1.5 * iqr
    else:
        # If IQR is 0, use percentile-based method
        if lower_percentile and upper_percentile:
            lower = non_null.quantile(lower_percentile / 100)
            upper = non_null.quantile(upper_percentile / 100)
        else:
            lower = non_null.min()
            upper = non_null.max()
    
    outlier_mask = (col_data < lower) | (col_data > upper)
    
    if method == 'remove':
        df = df[~outlier_mask]
    elif method == 'cap':
        df.loc[col_data < lower, column] = lower
        df.loc[col_data > upper, column] = upper
    elif method == 'transform_log':
        # Apply log transformation (only to positive values)
        positive_mask = col_data > 0
        df.loc[positive_mask, column] = np.log1p(col_data[positive_mask])
    elif method == 'transform_sqrt':
        # Apply square root transformation (only to non-negative values)
        non_negative_mask = col_data >= 0
        df.loc[non_negative_mask, column] = np.sqrt(col_data[non_negative_mask])
    elif method == 'winsorize':
        # Cap at specific percentiles
        if lower_percentile and upper_percentile:
            lower_bound = non_null.quantile(lower_percentile / 100)
            upper_bound = non_null.quantile(upper_percentile / 100)
        else:
            lower_bound = lower
            upper_bound = upper
        df.loc[col_data < lower_bound, column] = lower_bound
        df.loc[col_data > upper_bound, column] = upper_bound
    else:
        raise ValueError(f"Unknown outlier handling method: {method}")
    
    return df

def remove_duplicates(df, columns=None, keep='first'):
    """Remove duplicate rows."""
    if columns is None:
        return df.drop_duplicates(keep=keep)
    else:
        if isinstance(columns, str):
            columns = [columns]
        return df.drop_duplicates(subset=columns, keep=keep)

def normalize_column(df, column, method='min_max'):
    """Normalize a column using specified method."""
    if column not in df.columns:
        raise ValueError(f"Column '{column}' does not exist")
    
    col_data = pd.to_numeric(df[column], errors='coerce')
    
    if method == 'min_max':
        min_val = col_data.min()
        max_val = col_data.max()
        if max_val != min_val:
            df[column] = (col_data - min_val) / (max_val - min_val)
    elif method == 'z_score':
        mean_val = col_data.mean()
        std_val = col_data.std()
        if std_val > 0:
            df[column] = (col_data - mean_val) / std_val
    elif method == 'robust':
        median_val = col_data.median()
        mad = (col_data - median_val).abs().median()
        if mad > 0:
            df[column] = (col_data - median_val) / mad
    else:
        raise ValueError(f"Unknown normalization method: {method}")
    
    return df

def standardize_column(df, column):
    """Standardize a column (z-score normalization)."""
    return normalize_column(df, column, method='z_score')

def main():
    try:
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        operations = payload.get("operations", [])
        
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")
        
        # Load file
        df = load_file(file_path, file_type)
        original_rows = len(df)
        
        # Apply operations
        applied_operations = []
        
        for op in operations:
            op_type = op.get("type")
            op_method = op.get("method")
            op_column = op.get("column")
            op_value = op.get("value")
            op_columns = op.get("columns")
            op_lower_percentile = op.get("lower_percentile")
            op_upper_percentile = op.get("upper_percentile")
            
            if op_type == "impute_missing":
                df = impute_missing_values(df, op_column, op_method, op_value)
                applied_operations.append({
                    "type": "impute_missing",
                    "column": op_column,
                    "method": op_method,
                    "rows_affected": "calculated"
                })
            
            elif op_type == "handle_outliers":
                df = handle_outliers(df, op_column, op_method, op_lower_percentile, op_upper_percentile)
                applied_operations.append({
                    "type": "handle_outliers",
                    "column": op_column,
                    "method": op_method
                })
            
            elif op_type == "remove_duplicates":
                df = remove_duplicates(df, op_columns, op_method if op_method else 'first')
                applied_operations.append({
                    "type": "remove_duplicates",
                    "columns": op_columns,
                    "rows_removed": original_rows - len(df)
                })
            
            elif op_type == "normalize":
                df = normalize_column(df, op_column, op_method)
                applied_operations.append({
                    "type": "normalize",
                    "column": op_column,
                    "method": op_method
                })
            
            elif op_type == "standardize":
                df = standardize_column(df, op_column)
                applied_operations.append({
                    "type": "standardize",
                    "column": op_column
                })
            
            elif op_type == "remove_column":
                if op_column in df.columns:
                    df = df.drop(columns=[op_column])
                    applied_operations.append({
                        "type": "remove_column",
                        "column": op_column
                    })
            
            elif op_type == "remove_rows_with_missing":
                if op_column:
                    # Remove rows with NaN, empty strings, or whitespace-only strings in the column
                    str_col = df[op_column].astype(str)
                    # str_col.str.strip() == '' catches both empty strings and whitespace-only strings
                    mask = ~(df[op_column].isna() | (str_col.str.strip() == ''))
                    df = df[mask]
                else:
                    # Remove rows with any missing values (NaN, empty, or whitespace-only in any column)
                    # First remove NaN rows
                    df = df.dropna()
                    # Then remove rows where any column is empty or whitespace-only
                    mask = pd.Series([True] * len(df), index=df.index)
                    for col in df.columns:
                        str_col = df[col].astype(str)
                        # Keep rows where column is not empty/whitespace-only
                        col_mask = (str_col.str.strip() != '')
                        mask = mask & col_mask
                    df = df[mask]
                applied_operations.append({
                    "type": "remove_rows_with_missing",
                    "column": op_column,
                    "rows_removed": original_rows - len(df)
                })
        
        # Save cleaned file
        save_file(df, file_path, file_type)
        
        # Recalculate statistics
        result = {
            "success": True,
            "original_rows": original_rows,
            "cleaned_rows": len(df),
            "rows_removed": original_rows - len(df),
            "applied_operations": applied_operations,
            "message": f"Data cleaned successfully. {len(applied_operations)} operation(s) applied."
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
