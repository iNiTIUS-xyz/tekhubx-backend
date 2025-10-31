<?php

namespace App\Classes;

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class FileUploadClass
{
    // function for handle image uploads
    public function imageUploader($file, $path, $old_image = null)
    {

        if (!$file) {
            throw new \Exception('No file provided.');
        }

        if ($old_image) {
            $this->fileUnlink($old_image);
        }

        // Check if the file is an image
        if (in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'webp'])) {

            // Validate file extension
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                throw new \Exception('File type not supported for image upload.');
            }

            // Make image instance
            $img = Image::make($file)->orientate();

            $file_name = $path . '-' . uniqid() . '.' . $extension;
            // $file_name = $path . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

            $upload_path = 'public/' . $path . '/' . date('Y-m-d') . '/'; // Store in 'public' disk

            if (!Storage::exists($upload_path)) {
                Storage::makeDirectory($upload_path);
            }

            $img->save(storage_path('app/' . $upload_path . $file_name));

            return 'storage/' . str_replace('public/', '', $upload_path . $file_name);
        } else {
            throw new \Exception('File type not supported for image upload.');
        }
    }


    public function getImagePath($imagePath)
    {
        $path = '';
        if ($imagePath) {
            if (Storage::exists($imagePath)) {
                $path = asset($imagePath); // Correctly access the 'storage' path
            } else {
                $path = asset('storage/default/demo_user.png'); // Correct default path
            }
        } else {
            $path = asset('storage/default/demo_user.png'); // Default image
        }
        return $path;
    }


    public function fileUnlink($path)
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }

        return true;
    }

    // function for handle PDF uploads
    public function pdfUploader($file, $path, $old_file = null)
    {
        if ($old_file) {
            $this->fileUnlink($old_file);
        }

        // Ensure the file is a PDF
        if ($file->getClientOriginalExtension() === 'pdf') {
            $file_name = $path . '-' . uniqid() . '.' . $file->getClientOriginalExtension();

            $upload_path = 'public/' . $path . '/' . date('Y-m-d') . '/';

            if (!Storage::exists($upload_path)) {
                Storage::makeDirectory($upload_path);
            }

            $file->storeAs($upload_path, $file_name);

            return $upload_path . $file_name;
        } else {
            throw new \Exception('File type not supported for PDF upload.');
        }
    }
}
