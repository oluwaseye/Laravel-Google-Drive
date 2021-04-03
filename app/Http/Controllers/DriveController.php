<?php

namespace App\Http\Controllers;

use Exception;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DriveController extends Controller
{
    private $drive;
    private $client;
    public function __construct()
    {
        $client = app('Google_Client');
        $this->middleware(function ($request, $next) use ($client) {
            $accessToken = [
                'access_token' => auth()->user()->token,
                'created' => auth()->user()->created_at->timestamp,
                'expires_in' => auth()->user()->expires_in,
                'refresh_token' => auth()->user()->refresh_token
            ];

            $client->setAccessToken($accessToken);

            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                }
                auth()->user()->update([
                    'token' => $client->getAccessToken()['access_token'],
                    'expires_in' => $client->getAccessToken()['expires_in'],
                    'created_at' => $client->getAccessToken()['created'],
                ]);
            }

            $client->refreshToken(auth()->user()->refresh_token);
            $this->drive = new Google_Service_Drive($client);
            return $next($request);
        });
    }

    public function getDrive()
    {
        $this->ListFolders('root');
    }

    public function ListFolders($id)
    {

        $query = "visibility='anyoneCanFind' or visibility='anyoneWithLink' and mimeType != 'application/vnd.google-apps.folder' and '" . $id . "' in parents and trashed=false";

        $optParams = [
            'fields' => 'files(id, name, mimeType, thumbnailLink, size, ownedByMe, originalFilename, hasThumbnail, sharedWithMeTime, webViewLink, webContentLink, iconLink)',
            'q' => $query,
            'pageSize' => 500,
        ];

        $results = $this->drive->files->listFiles($optParams);

        if (count($results->getFiles()) == 0) {
            print "No files found.\n";
        } else {
            echo "Files", "<br><br>";
            // echo "<pre>";
            foreach ($results->getFiles() as $file) {
                //   dump($file->getName(), $file->getID());

                //  \print_r($file);

                echo "<strong>file id</strong>: " . $file->id .
                    "<br><strong>file name</strong>: " . $file->name .
                    " <br><strong>file ext</strong>: " . $file->mimeType .
                    " <br><strong>file size</strong>: " . $file->size .
                    " <br><strong>file hasThumbnail</strong>: " . $file->hasThumbnail .
                    " <br><strong>file iconLink</strong>: " . $file->iconLink .
                    " <br><strong>file thumbnailLink</strong>: " . $file->thumbnailLink .
                    " <br><strong>file webContentLink</strong>: " . $file->webContentLink .
                    " <br><strong>file webViewLink</strong>: " . $file->webViewLink .
                    " <br><br>";
            }
            // echo "</pre>";
        }
    }

    function uploadFile(Request $request)
    {
        if ($request->isMethod('GET')) {
            return view('upload');
        } else {
            $this->createFile($request->file('file'), $request->input('foldername'), $request->input('filename'));
        }
    }

    function createStorageFile($storage_path)
    {
        $this->createFile($storage_path);
    }

    function createFile($file, $parent_id = null, $filename)
    {
        $name = gettype($file) === 'object' ?  $filename : $file->getClientOriginalName();
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $name,
            'parent' => $parent_id ? $parent_id : 'shabango' //folder
        ]);

        $content = gettype($file) === 'object' ?  File::get($file) : Storage::get($file);
        $mimeType = gettype($file) === 'object' ? File::mimeType($file) : Storage::mimeType($file);

        $file = $this->drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        dd($file);
    }

    function deleteFileOrFolder($id)
    {
        try {
            $this->drive->files->delete($id);
        } catch (Exception $e) {
            return false;
        }
    }

    function createFolder($folder_name)
    {
        $folder_meta = new Google_Service_Drive_DriveFile(array(
            'name' => $folder_name,
            'mimeType' => 'application/vnd.google-apps.folder'
        ));
        $folder = $this->drive->files->create($folder_meta, array(
            'fields' => 'id'
        ));
        return $folder->id;
    }
}
