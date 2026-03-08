<?php

namespace MetForm_Pro\Core\Integrations\Google_Drive;


use MetForm_Pro\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

class MF_Google_Drive {

    use Singleton;

    // Upload files to Google Drive
    public function upload_files_to_drive( $file_upload_info, $folder_id = null) {
        if (empty($file_upload_info)) {
            return false;
        }

        // Validate access token early
        $google_sheet = \MetForm_Pro\Core\Integrations\Google_Sheet\WF_Google_Sheet::instance();
        $arr_token = $google_sheet->token();
        if ($arr_token === false || empty($arr_token->access_token)) {
            return false;  // Invalid or missing token
        }
        $access_token = $arr_token->access_token;

        $uploaded_files = [];

        foreach ($file_upload_info as $field_name => $files) {
            if (!is_array($files)) continue;  // Ensure files is an array

            foreach ($files as $file) {
                // Fix: Use 'file' key instead of 'path' (based on logs)
                if (!isset($file['file']) || !file_exists($file['file'])) {
                    continue;  // Skip invalid files
                }

                $file_path = $file['file'];
                $file_content = file_get_contents($file_path);
                if ($file_content === false) {
                    continue;  // Skip if file can't be read
                }

                $file_name = basename($file_path);
                $mime_type = mime_content_type($file_path) ?: 'application/octet-stream';  // Fallback MIME type

                // Prepare metadata
                $metadata = ['name' => $file_name];
                if (!empty($folder_id) && is_string($folder_id)) {  // Ensure folder_id is a string
                    $metadata['parents'] = [$folder_id];
                }

                // Build multipart request
                $boundary = '-------314159265358979323846';
                $delimiter = "\r\n--" . $boundary . "\r\n";
                $close_delim = "\r\n--" . $boundary . "--";
                $postData = $delimiter .
                    'Content-Type: application/json' . "\r\n\r\n" .
                    json_encode($metadata) . $delimiter .
                    'Content-Type: ' . $mime_type . "\r\n" .
                    'Content-Transfer-Encoding: base64' . "\r\n\r\n" .
                    base64_encode($file_content) . $close_delim;

                $response = wp_remote_post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $access_token,
                        'Content-Type' => 'multipart/related; boundary="' . $boundary . '"',
                        'Content-Length' => strlen($postData)
                    ],
                    'body' => $postData,
                    'timeout' => 60
                ]);
                
                if (is_wp_error($response)) {
                    continue;  // Skip on network error
                }

                $response_body = wp_remote_retrieve_body($response);
                $file_data = json_decode($response_body, true);

                if (isset($file_data['id'])) {
                    $uploaded_files[] = [
                        'field_name' => $field_name,
                        'original_name' => $file_name,
                        'drive_id' => $file_data['id'],
                        'drive_url' => 'https://drive.google.com/file/d/' . $file_data['id'] . '/view'
                    ];
                }
            }
        }

        return $uploaded_files ?: false;  // Return false if no files uploaded
    }

    // Insert files and update form data
    public function insert_file($form_id, $title, $form_data, $file_upload_info, $attributes, $folder_id = null) {
        // Validate inputs
        if (empty($file_upload_info) || !is_array($file_upload_info)) {
            return false;  // No files to upload
        }

        // Extract folder_id from array if passed (e.g., from action.php)
        $target_folder_id = null;
        if (is_array($folder_id) && isset($folder_id['folder_id'])) {
            $target_folder_id = $folder_id['folder_id'];
        } elseif (is_string($folder_id)) {
            $target_folder_id = $folder_id;
        }

        if (empty($target_folder_id)) {
            return false;  // No valid folder
        }

        // Upload files
        $uploaded_files = $this->upload_files_to_drive( $file_upload_info, $target_folder_id);

        if ($uploaded_files === false) {
            return false;  // Upload failed (e.g., token issue)
        }

        // Update form_data with Google Drive URLs
        if (!empty($uploaded_files)) {
            foreach ($uploaded_files as $uploaded_file) {
                $field_name = $uploaded_file['field_name'];
                if (isset($form_data[$field_name])) {
                    $drive_urls_key = $field_name . '_drive_urls';
                    if (!isset($form_data[$drive_urls_key])) {
                        $form_data[$drive_urls_key] = '';
                    }
                    $form_data[$drive_urls_key] .= $uploaded_file['drive_url'] . ', ';
                }
            }
        }

        return true;  // Success
    }

    public function get_all_google_drive_folders( ) {    
        // google access token
        $google_sheet = \MetForm_Pro\Core\Integrations\Google_Sheet\WF_Google_Sheet::instance();
        $arr_token = $google_sheet->token();
        if($arr_token == false) {
            return [];
        }
        $access_token = $arr_token->access_token;

        $url = add_query_arg(
            [
                'q'        => "mimeType='application/vnd.google-apps.folder' and trashed=false",
                'pageSize' => 100,
                'fields'   => 'files(id,name)',
            ],
            'https://www.googleapis.com/drive/v3/files'
        );

        $response = wp_remote_get( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
            'timeout' => 20,
        ] );
        
        if ( is_wp_error( $response ) ) return [];

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( empty( $data['files'] ) ) return [];
        $folders = [];
        foreach ( $data['files'] as $f ) {
            $folders[] = [
                'id'   => $f['id'],
                'name' => $f['name'],
            ];
        }
        
        return $folders;
    }


}

