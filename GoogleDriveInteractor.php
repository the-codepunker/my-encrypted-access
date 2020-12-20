<?php
namespace Codepunker\GoogleDrive;

/**
 * interacts with google drive
 * finds, downloads and updates gdrive files
 */
class GoogleDriveInteractor
{
    private $service;
    
    /**
     * sets the google client instance
     * @param Google_Client $service the google client instance
     */
    public function __construct($service)
    {
        $this->service = $service;
    }

    /**
     * searches for a file on gDrive and throws if it doesn't find
     * the exact no. of files we want to find
     * @param  Array       $query    a list of filters and other params for the query
     * @param  Int|integer $expected how many results to expect - 0 to disable
     * @return Google_Service_Drive_DriveFile || Google_Service_Drive_FileList
     */
    public function findFiles(array $query, int $expected = 0)
    {
        if (empty($query)) {
            throw new \Exception("You must first specify the search query", 1);
        }

        $files = $this->service->files->listFiles($query);

        if ($expected>0 && count($files)!==$expected) {
            $msg = "Expected no. of files returned is different from the actual search result. 
            Expected {$expected}, returned " .count($files);
            throw new \Exception($msg . "\n", 1);
        }

        return (count($files)===1) ? $files[0] : $files;
    }

    /**
     * updates a file in google drive
     * @param  Google_Service_Drive_DriveFile $drive_file file to be updated
     * @param  string                         $data       data to write to gdrive file
     * @return Google_Service_Drive_DriveFile
     */
    public function updateFile(\Google_Service_Drive_DriveFile $drive_file, string $data)
    {
        $new_drive_file = new \Google_Service_Drive_DriveFile();

        $additionalParams = [
            'data' => $data,
            'mimeType' => 'text/plain'
        ];

        // Send the request to the API.
        $updatedFile = $this->service->files->update($drive_file->id, $new_drive_file, $additionalParams);
        return $updatedFile;
    }

    
    public function uploadNewFile(string $data)
    {
        $new_drive_file = new \Google_Service_Drive_DriveFile();
        $new_drive_file->setName(__FILE_NAME__);
        $new_drive_file->setDescription(__DESCRIPTION__);
        $new_drive_file->setMimeType('text/plain');

        $additionalParams = [
            'data' => $data,
            'mimeType' => 'text/plain'
        ];

        // Send the request to the API.
        $createdFile = $this->service->files->create($new_drive_file, $additionalParams);
        return $createdFile;
    }



    /**
     * downloads the contents of a file
     * @param  string $file_id id of the file in gdrive
     * @return string          file contents
     */
    public function downloadFile(string $file_id)
    {
        $f = $this->service->files->get($file_id, ['alt'=>'media']);
        return $f->getBody()->getContents();
    }
}
