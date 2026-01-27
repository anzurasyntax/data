## Data Cleaning Web App

This project is a **Laravel 12 + Python–powered data cleaning dashboard**. Authenticated users can upload tabular data files (CSV, TXT, XML, XLSX), inspect them in an interactive web UI, run an automated data‑quality assessment, and apply advanced cleaning operations, all from the browser.

### How it Works (High‑Level Flow)

- **User authentication**
  - Register, login, logout using Laravel’s built‑in auth (session based).
  - Guests are redirected to the login/register pages; only authenticated users can manage files and run cleaning.

- **File upload & storage**
  - Users upload a file and select the file type (`txt`, `csv`, `xml`, `xlsx`) on the upload screen (`files.create` view).
  - `UploadedFileController` and `UploadedFileService` store files in `storage/app/public/...` and create a DB record (with user ownership and slug).

- **Automatic quality check**
  - After upload, Laravel immediately calls the Python script `quality_check.py` via `PythonProcessingService`.
  - The script loads the file with **pandas**, computes:
    - Overall **quality score** (0–100)
    - Counts of rows, columns, missing values, duplicate rows, and numeric outliers
    - Per‑column stats: data type detection, missing %, duplicates, outliers, basic numeric stats
  - The results are shown in the **Data Quality Report** screen (`files.quality` view) with Tailwind‑styled cards and tables.

- **File listing & management**
  - `FileProcessingController@index` and `UploadedFileController@index` list only the logged‑in user’s files.
  - From the list, users can:
    - Open the **preview/cleaning** view
    - Open the **quality report** again
    - **Delete** a file (removes both the physical file and the DB record).

- **Interactive preview & inline editing**
  - The preview page (`files.preview` view) loads data by calling `process_file.py` through `PythonProcessingService`.
  - `process_file.py`:
    - Loads the file with pandas
    - Returns rows, column list, per‑column stats, duplicate counts, and an outlier map as JSON
  - The Blade view renders:
    - A fixed left **sidebar** with row/column counts, duplicates, empty-cell count, filtered-row count
    - A scrollable, “Excel‑like” table with per‑cell hover states
    - Client‑side filters (backed by a Web Worker via `filter-worker.js`) to avoid blocking the UI on large datasets
  - Clicking a cell allows **inline editing**:
    - Frontend sends an AJAX `PUT /files/{slug}/cell` request.
    - `FileProcessingController@updateCell` calls `update_cell.py`, which updates the underlying file and recomputes key stats.

- **Advanced data cleaning operations**
  - The preview screen includes an **“Advanced Data Cleaning Tools”** panel.
  - When the user applies cleaning, the frontend builds an `operations` array and calls `POST /files/{slug}/clean`.
  - `FileProcessingController@cleanData` passes this payload to `clean_data.py`, which:
    - Loads the file with pandas
    - Applies the requested operations, such as:
      - **Missing values**: mean/median/mode imputation, forward/backward fill, interpolation, constant value, row/column removal
      - **Outliers**: removal, capping, log/sqrt transform, winsorization with optional percentiles
      - **Duplicates**: remove duplicates on all or selected columns
      - **Normalization/standardization**: min‑max, z‑score, robust scaling
      - **Column / row pruning**: drop columns, drop rows with missing values (per column or across all columns)
    - Saves the cleaned data back to the same file path in its original format
    - Returns a summary (original rows, cleaned rows, rows removed, and list of applied operations)
  - The backend returns this summary as JSON; the UI can refresh stats/quality to reflect the cleaned dataset.

### Routes & Responsibilities (Web Layer)

- **Public**
  - `GET /` → redirects to login or upload page based on auth.
  - `GET /login`, `POST /login` → `AuthController` login.
  - `GET /register`, `POST /register` → `AuthController` registration.

- **Authenticated**
  - `POST /logout` → log out current user.
  - `GET /files` → upload page + list of user’s files (`UploadedFileController@index`).
  - `POST /files` → upload and store a new file and run initial quality check (`UploadedFileController@store`).
  - `DELETE /files/{slug}` → delete a file (`UploadedFileController@destroy`).
  - `GET /files/{slug}/quality` → render quality report (`UploadedFileController@quality`).
  - `GET /my-files` → list files in a separate index view (`FileProcessingController@index`).
  - `GET /my-files/{slug}` → preview & cleaning UI (`FileProcessingController@show`).
  - `PUT /files/{slug}/cell` → inline cell update via Python (`FileProcessingController@updateCell`).
  - `POST /files/{slug}/clean` → run batch cleaning operations via Python (`FileProcessingController@cleanData`).
  - `GET /files/{slug}/quality-check` → JSON‑only quality check API (`FileProcessingController@qualityCheck`).

### Technology Stack

- **Backend**
  - **Language / Framework**: PHP 8.2+, **Laravel 12**
  - **Auth & security**: Laravel auth + middleware; **Laravel Sanctum** is installed for token‑based access (used for APIs if needed).
  - **Inter‑process bridge**: `symfony/process` to securely call Python scripts and capture stdout/stderr.
  - **Data model**: `UploadedFile` model with per‑user ownership, slug routing, and metadata (type, size, path).
  - **Queues / tooling**: Laravel queue & logging are configured; dev script runs `php artisan queue:listen` and `php artisan pail` alongside the HTTP server and Vite.

- **Frontend**
  - **Views**: Laravel Blade templates in `resources/views` (`auth`, `files.create`, `files.index`, `files.preview`, `files.quality`).
  - **Styling**: **Tailwind CSS** via CDN plus a small custom stylesheet (`public/assets/css/app.css`).
  - **JavaScript**:
    - Inline script in `files.preview` for filters, inline editing, and calling the JSON endpoints.
    - Web Worker (`filter-worker.js`) for client‑side row filtering on large tables without blocking the main thread.
  - **Bundler**: Vite (configured in `vite.config.js`) to build assets from `resources/js` and `resources/css`.

- **Python data‑processing layer**
  - **Runtime**: Python 3 (path configurable via `PYTHON_PATH` in `.env`).
  - **Dependencies** (from `python/requirements.txt`):
    - `pandas` (tabular data handling)
    - `numpy` (numeric operations)
    - `openpyxl` (Excel I/O)
    - `lxml` (XML parsing)
  - **Scripts**:
    - `process_file.py` → load file, compute column statistics, detect duplicates/outliers, and return preview data.
    - `clean_data.py` → apply user‑selected cleaning operations and save the cleaned dataset.
    - `quality_check.py` → calculate overall quality score and detailed per‑column quality metrics.
    - `update_cell.py` → update a single cell and recalculate local statistics (used by inline editing).
  - All scripts accept a JSON payload on `argv[1]` and return a JSON object on stdout. `PythonProcessingService` normalizes paths, validates that file paths stay inside `storage/app/public`, handles timeouts, and converts Python errors to Laravel exceptions with detailed logs.

### Setup & Requirements (Summary)

- **Prerequisites**
  - PHP 8.2+, Composer
  - Node.js + npm
  - Python 3 with ability to install `pandas`, `numpy`, `openpyxl`, `lxml`

- **Install & run**
  - Copy `.env.example` to `.env`, configure DB and filesystem.
  - Install PHP dependencies and run the helper script:
    - `composer run setup`  
      (installs PHP deps, generates app key, runs migrations, installs Node deps, builds assets)
  - Install Python deps:
    - `cd python && pip install -r requirements.txt`
  - Start the dev stack (Laravel server, queue listener, logs, and Vite) from the project root:
    - `composer run dev`

- **Python path configuration (optional)**
  - Set `PYTHON_PATH` in `.env` if the Python binary is not available as `python` in your system `PATH`:
    - `PYTHON_PATH="C:\\Path\\To\\python.exe"`

With this setup, the application provides an end‑to‑end workflow: **upload → quality analysis → interactive preview → targeted cleaning → re‑evaluation**, all in a modern, Tailwind‑styled Laravel UI backed by robust Python data‑processing.
