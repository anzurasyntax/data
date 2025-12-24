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
    
    # Validate file exists
    if not os.path.exists(file_path):
        raise FileNotFoundError(f"File not found: {file_path}")
    
    # Validate file type
    allowed_types = ['csv', 'txt', 'xml', 'xlsx']
    if file_type not in allowed_types:
        raise ValueError(f"Unsupported file type: {file_type}. Allowed types: {', '.join(allowed_types)}")
    
    try:
        if file_type == 'csv':
            df = pd.read_csv(file_path)
        elif file_type == 'txt':
            # Try CSV first, then space-separated, then tab-separated
            try:
                df = pd.read_csv(file_path, sep=',')
            except:
                try:
                    df = pd.read_csv(file_path, sep='\t')
                except:
                    df = pd.read_csv(file_path, sep='\s+')
        elif file_type == 'xml':
            df = pd.read_xml(file_path)
        elif file_type == 'xlsx':
            try:
                df = pd.read_excel(file_path, engine='openpyxl')
            except ImportError:
                raise ImportError("openpyxl library is required for Excel files. Install it with: pip install openpyxl")
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
            # Save as CSV format for text files
            df.to_csv(file_path, index=False, sep='\t')
        elif file_type == 'xml':
            df.to_xml(file_path, index=False)
        elif file_type == 'xlsx':
            try:
                df.to_excel(file_path, index=False, engine='openpyxl')
            except ImportError:
                raise ImportError("openpyxl library is required for Excel files. Install it with: pip install openpyxl")
            except Exception as e:
                raise Exception(f"Failed to write Excel file: {str(e)}")
        else:
            raise ValueError(f"Unsupported file type: {file_type}")
    except Exception as e:
        raise Exception(f"Error saving file: {str(e)}")

def calculate_column_stats(df, col):
    """Calculate statistics for a column."""
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
        try:
            q1 = non_null_numeric.quantile(0.25)
            q3 = non_null_numeric.quantile(0.75)
            iqr = q3 - q1
            
            # Handle case where IQR is 0 or very small
            if iqr > 0:
                lower = q1 - 1.5 * iqr
                upper = q3 + 1.5 * iqr
                
                outlier_mask = (numeric_values < lower) | (numeric_values > upper)
                outlier_indexes = outlier_mask[outlier_mask].index.tolist()
                outlier_count = len(outlier_indexes)
            
            stats = {
                "min": round(float(non_null_numeric.min()), 2),
                "max": round(float(non_null_numeric.max()), 2),
                "mean": round(float(non_null_numeric.mean()), 2),
                "median": round(float(non_null_numeric.median()), 2),
                "std": round(float(non_null_numeric.std()), 2) if len(non_null_numeric) > 1 else 0.0
            }
        except Exception as e:
            # If statistics calculation fails, just skip stats
            pass
    
    return {
        "empty_count": int(empty_count),
        "empty_percentage": round((empty_count / len(df)) * 100, 1) if len(df) > 0 else 0,
        "duplicate_count": int(col_data.duplicated().sum()),
        "data_type": detected_type,
        "unique_values": int(col_data.nunique()),
        "outlier_count": int(outlier_count),
        "stats": stats
    }, outlier_indexes

def main():
    try:
        # Parse payload
        payload = json.loads(sys.argv[1])
        file_path = payload.get("file_path")
        file_type = payload.get("file_type")
        row_index = payload.get("row_index")
        column = payload.get("column")
        new_value = payload.get("value", "")
        
        # Validate required fields
        if not file_path or not file_type:
            raise ValueError("file_path and file_type are required")
        if row_index is None or column is None:
            raise ValueError("row_index and column are required")
        
        # Load file
        df = load_file(file_path, file_type)
        
        # Validate row and column exist
        if row_index < 0 or row_index >= len(df):
            raise ValueError(f"Row index {row_index} is out of range (0-{len(df)-1})")
        if column not in df.columns:
            raise ValueError(f"Column '{column}' does not exist in the file")
        
        # Update cell value
        if new_value == "" or new_value is None:
            df.at[row_index, column] = np.nan
        else:
            df.at[row_index, column] = new_value
        
        # Save file
        save_file(df, file_path, file_type)
        
        # Recalculate statistics
        column_stats = {}
        outlier_map = {}
        
        for col in df.columns:
            try:
                col_stats, outlier_indexes = calculate_column_stats(df, col)
                column_stats[col] = col_stats
                
                for idx in outlier_indexes:
                    if str(idx) not in outlier_map:
                        outlier_map[str(idx)] = {}
                    outlier_map[str(idx)][col] = True
            except Exception as e:
                # Skip columns that fail analysis
                column_stats[col] = {
                    "empty_count": 0,
                    "empty_percentage": 0,
                    "duplicate_count": 0,
                    "data_type": "unknown",
                    "unique_values": 0,
                    "outlier_count": 0,
                    "stats": {}
                }
        
        # Get updated value
        updated_value = df.at[row_index, column]
        if pd.isna(updated_value):
            updated_value = None
        
        result = {
            "success": True,
            "column_stats": column_stats,
            "total_duplicate_rows": int(df.duplicated().sum()),
            "outlier_map": outlier_map,
            "updated_value": updated_value
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
