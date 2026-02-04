<?php
namespace App\Service;

use Shuchkin\SimpleXLSXGen;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ExcelUtilService
{
    public static function export($data, $filename)
    {
        // $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        // $response = new Response($serializer->encode($data, CsvEncoder::FORMAT));
        // $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        // $response->headers->set('Content-Disposition', "attachment; filename=\"$filename\"");
        // return $response;
        // dd($data);
        $xlsx = SimpleXLSXGen::fromArray( $data );
        $xlsx->saveAs('.\\excels\\'.$filename.'.xlsx');
        $xlsx->downloadAs($filename.'.xlsx'); // or downloadAs('books.xlsx') or $xlsx_content = (string) $xlsx 
        $response = new Response($xlsx);
        exit();
        // return $response;      
    }

    public static function import($filename, $options = [])
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        return $serializer->decode(file_get_contents($filename), CsvEncoder::FORMAT, $options);
    }
}
?>