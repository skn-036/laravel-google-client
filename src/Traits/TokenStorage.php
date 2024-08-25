<?php
namespace Skn036\Google\Traits;

use Illuminate\Support\Facades\Storage;

trait TokenStorage
{
    /**
     * Path to the token folder.
     *
     * @var string
     *
     * @return bool
     */
    protected function isCredentialsFileExists($filePath)
    {
        return Storage::disk('local')->exists($filePath);
    }

    /**
     * Parse the json data from the file.
     *
     * @param string $filePath
     *
     * @return array<string, mixed>|null
     */
    private function parseJsonDataFromFile($filePath)
    {
        if (!$this->isCredentialsFileExists($filePath)) {
            return null;
        }
        $content = decrypt(Storage::disk('local')->get($filePath));
        return json_decode($content, true);
    }

    /**
     * Save the json data to the file.
     *
     * @param string $filePath
     * @param array<string, mixed> $data
     *
     * @return void
     */
    private function saveJsonDataToFile($filePath, $data)
    {
        $content = json_encode($data);
        Storage::disk('local')->put($filePath, encrypt($content));
    }

    /**
     * Delete the file.
     *
     * @param string $filePath
     *
     * @return bool
     */
    private function deleteFile($filePath)
    {
        if (!$this->isCredentialsFileExists($filePath)) {
            return true;
        }
        return Storage::disk('local')->delete($filePath);
    }
}
