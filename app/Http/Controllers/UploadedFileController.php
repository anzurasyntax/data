<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadedFileRequest;
use App\Models\UploadedFile;
use App\Services\UploadedFileService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UploadedFileController extends Controller
{
    public function __construct(
        private UploadedFileService $service
    ) {}

    public function index(): Factory|View
    {
        $files = UploadedFile::all();
        return view('files.create', compact('files'));
    }

    public function store(StoreUploadedFileRequest $request): RedirectResponse
    {
        $this->service->store($request->file_type, $request->file('file'));

        return redirect()->route('process.index');
    }


}
