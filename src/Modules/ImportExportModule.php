<?php

namespace ShopwareBot\Modules;

use ShopwareBot\Exceptions\ImportExportException;

/**
 * Class ImportExportModule
 * @package ShopwareBot\Modules
 */
class ImportExportModule extends BaseModule
{

    /**
     *
     */
    const ARTICLES_COMPLETE = 3;
    /**
     *
     */
    const ARTICLE_IMAGES_PROFILE = 20;

    /**
     *
     */
    const CSV_DIRECTORY = __DIR__ . '/csv/';

    /**
     * @param $filename
     * @param string $savefilename
     * @return bool
     */
    public function download($filename, $savefilename = '')
    {
        $_savefilename = self::CSV_DIRECTORY . $filename;
        if ($savefilename) $_savefilename = self::CSV_DIRECTORY . $savefilename;
        return $this->client->download('/SwagImportExport/downloadFile?fileName=' . $filename, $_savefilename);
    }

    /**
     * @return mixed
     */
    public function getProfiles()
    {
        $res = $this->client->get('/SwagImportExportProfile/getProfiles');
        return $res->data;
    }

    /**
     * @param $profileId
     * @param string $limit
     * @param string $offset
     * @param string $format
     * @return array
     */
    public function prepareExportParameters($profileId, $limit = '', $offset = '', $format = 'csv')
    {
        if (!is_int($profileId))
            throw new ImportExportException('profileId must be an integer.');

        return [
            'profileId' => $profileId,
            'sessionId' => '',
            'format' => $format,
            'limit' => $limit,
            'offset' => $offset,
            'categories' => '',
            'productStreamId' => '',
            'variants' => 'on',
            'ordernumberFrom' => '',
            'dateFrom' => '',
            'dateTo' => '',
            'orderstate' => '',
            'paymentstate' => '',
            'stockFilter' => 'all',
            'customFilterDirection' => 'greaterThan',
            'customFilterValue' => '',
            'customerStreamId' => '',
            'customerId' => '',
        ];

    }

    /**
     * @param $data
     * @return mixed
     */
    public function prepareExport($data)
    {
        $prepare = $this->client->post('/SwagImportExportExport/prepareExport', $data);

        if (!$prepare)
            throw new ImportExportException('Could not prepare for exporting. HttpCode:' . $this->client->getCurl()->getHttpStatusCode());
        if (!$prepare->success)
            throw new ImportExportException($prepare->msg);

        return $prepare;
    }

    /**
     * @param $profileId
     * @param string $limit
     * @param string $offset
     * @param string $format
     * @return array|bool
     */
    public function export($profileId, $limit = '', $offset = '', $format = 'csv')
    {
        $data = $this->prepareExportParameters($profileId, $limit, $offset, $format);
        $prepare = $this->prepareExport($data);
        $count = $prepare->count;
        if (!$prepare->success) return false;
        $export = $this->client->post('/SwagImportExportExport/export', $data);
        if (!$export || !$export->success) return false;

        $sessionId = $export->data->sessionId;
        $fileName = $export->data->fileName;

        while ($export->data->position < $count) {

            $new_data = [
                'profileId' => $profileId,
                'type' => 'export',
                'format' => $format,
                'sessionId' => $sessionId,
                'fileName' => $fileName,
                'limit' => $limit,
                'offset' => $offset,
                'position' => $export->data->position,
            ];
            $export = $this->client->post('/SwagImportExportExport/export', $new_data);
        }
        $prefix = str_replace('.', '_', $this->client->getUrlHost());
        $savefilename = $prefix . '_' . $fileName;
        return [
            'success' => $this->download($fileName, $savefilename),
            'filename' => $savefilename,
            'path' => self::CSV_DIRECTORY . $savefilename
        ];

    }

    /**
     * @param $profileId
     * @param string $limit
     * @param string $offset
     * @param string $format
     * @return array
     * @throws ImportExportException
     */
    public function exportPartially($profileId, $limit = '', $offset = '', $format = 'csv')
    {
        $data = $this->prepareExportParameters($profileId, $limit, $offset, $format);
        $prepare = $this->prepareExport($data);
        $split_count = round($prepare->count / 2);


        $part1 = $this->export($profileId, $split_count);
        $part2 = $this->export($profileId, '', $split_count);

        $arr1 = $this->csv_to_array(self::CSV_DIRECTORY . $part1['filename']);
        $arr2 = $this->csv_to_array(self::CSV_DIRECTORY . $part2['filename']);

        $prefix = str_replace('.', '_', $this->client->getUrlHost());

        $output_filename = $prefix . '_' . pathinfo($part1['filename'], PATHINFO_FILENAME) . '-' . pathinfo($part2['filename'], PATHINFO_FILENAME);
        $merged_file = $this->array_to_csv(array_merge($arr1, $arr2), self::CSV_DIRECTORY . $output_filename);


        return [
            'part1' => $part1,
            'part2' => $part2,
            'merged' => [
                'status' => $merged_file,
                'filename' => $output_filename . '.csv',
                'path' => self::CSV_DIRECTORY . $output_filename . '.csv'
            ]
        ];
    }

    /**
     * TODO: Upload multipart yapılamadı tekrar bakılacak
     *
     * @param $filepath
     * @param int $profileId
     * @return mixed
     */
    public function uploadFileForImport($filepath, $profileId = 3)
    {
//        $myfile = curl_file_create($file, 'application/vnd.ms-excel', basename($file));
//
//        $this->client->getCurl()->setHeader('Content-Type', 'multipart/form-data');
//        $res = $this->client->post('/SwagImportExportExport/uploadFile', [
//            'importFile' => $myfile,
//            '__csrf_token' => $this->client->getCsrfToken(),
//            'profile' => $profileId,
//
//        ]);


        $fields = [
            '__csrf_token' => $this->client->getCsrfToken(),
            'importFile' => basename($filepath),
            'profile' => $profileId
        ];

        $files = [];
        $files[$filepath] = file_get_contents($filepath);

        $boundary = uniqid();
        $delimiter = '-------------WebKitFormBoundary' . $boundary;
        $post_data = $this->client->build_data_files($boundary, $fields, $files);

//        $this->client->getCurl()->setHeaders([
//            'content-type: ' . "multipart/form-data; boundary=" . $delimiter,
//            'Content-Length:' . strlen($post_data)
//        ]);
        $this->client->getCurl()->setHeader('content-type', "multipart/form-data; boundary=" . $delimiter);
        $this->client->getCurl()->setHeader('Content-Length', strlen($post_data));

        $f = fopen('request.txt', 'w');
        curl_setopt($this->client->getCurl()->curl,CURLOPT_STDERR ,$f);
        curl_setopt($this->client->getCurl()->curl, CURLOPT_VERBOSE, true);

        $res = $this->client->post('/SwagImportExportExport/uploadFile', $post_data);

        return $res;
    }


    /**
     * @param $filename
     * @param string $delimiter
     * @param bool $associative
     * @param bool $associative_key_index
     * @param bool $utf8_encode
     * @return array
     */
    public function csv_to_array($filename, $delimiter = ';', $associative = true, $associative_key_index = false, $utf8_encode = false)
    {
        $csv_array = $fields = array();
        $i = 0;
        if (($handle = fopen($filename, "r")) !== FALSE) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                if ($utf8_encode)
                    $row = array_map("utf8_encode", $row); //added

                if ($associative) {
                    if (empty($fields)) {
                        $fields = $row;
                        continue;
                    }

                    $csv_array_item_key = $i;
                    if ($associative_key_index !== false && $associative_key_index >= 0)
                        $csv_array_item_key = $row[$associative_key_index];

//                $key_index = $i;
//                if (strlen($associative_key) > 0) {
//                    $j = 0;
//                    foreach ($fields as $field) {
//                        if ($associative_key == $field) {
//                            $key_index = $j;
//                        }
//                        $j++;
//                    }
//                }
//                print_r($fields);
//                print_r($fields[$key_index]);
                    foreach ($row as $k => $value) {
                        $csv_array[$csv_array_item_key][$fields[$k]] = $value;
                    }

                } else {
                    $csv_array[$i] = $row;
                }
                $i++;

                //print_r($row);
            }
            fclose($handle);
        }
        return $csv_array;
    }

    /**
     * @param $data
     * @param string $filename
     * @param string $delimiter
     * @return bool
     */
    public function array_to_csv($data, $filename = '', $delimiter = ';')
    {
        if (empty($filename)) $filename = date('YmdHis');
        $filename = $filename . '.csv';
        $file = fopen($filename, 'w') or die("Can't open " . $filename);

        $tmp = true;
        foreach ($data as $item) {
            if ($tmp) {
                fputcsv($file, array_keys($item), $delimiter);
                $tmp = false;
            }
            fputcsv($file, $item, $delimiter);
        }
        fclose($file) or die("Can't close $filename");

        return true;
    }

    /**
     * @param $data
     * @param string $filename
     * @param string $delimiter
     */
    public function csv_flush($data, $filename = '', $delimiter = ';')
    {
        if (empty($filename)) $filename = date('YmdHis');
        $output = fopen("php://output", 'w') or die("Can't open php://output");
        header("Content-Type: text/csv; charset=UTF-8;");
        header("Content-Disposition: attachment; filename=" . $filename . ".csv");

        $tmp = true;
        foreach ($data as $item) {
            if ($tmp) {
                fputcsv($output, array_keys($item), $delimiter);
                $tmp = false;
            }
            fputcsv($output, $item, $delimiter);
        }
        fclose($output) or die("Can't close php://output");
        die;
    }


}