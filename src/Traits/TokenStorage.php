<?php

namespace Skn036\Google\Traits;

use Illuminate\Support\Facades\Storage;

trait TokenStorage
{
    /**
     * Path to the token folder.
     *
     * @param string $filePath
     * @return bool
     */
    protected function isCredentialsFileExists($filePath)
    {
        return Storage::disk('local')->exists($filePath);
    }

    /**
     * Parse the json data from the file.
     *
     * @param  string  $filePath
     * @return array<string, mixed>
     */
    private function parseJsonDataFromFile($filePath, $encrypt = true)
    {
        $defaultReturn = [
            'accounts' => [],
            'default_account' => null,
        ];
        try {
            if (!$this->isCredentialsFileExists($filePath)) {
                return $defaultReturn;
            }

            $read = Storage::disk('local')->get($filePath);
            $content = $encrypt ? decrypt($read) : $read;

            $content = json_decode($content, true);
            if (!is_array($content)) {
                return $defaultReturn;
            }
            return $content;
        } catch (\Exception $error) {
            return $defaultReturn;
        }
    }

    /**
     * Save the json data to the file.
     *
     * @param  string  $filePath
     * @param  array<string, mixed>  $data
     * @return bool
     */
    private function saveJsonDataToFile($filePath, $data, $encrypt = true)
    {
        $content = json_encode($data);
        return Storage::disk('local')->put($filePath, $encrypt ? encrypt($content) : $content);
    }

    /**
     * Delete the file.
     *
     * @param  string  $filePath
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
