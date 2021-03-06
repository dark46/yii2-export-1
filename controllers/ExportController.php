<?php
/**
 * Created by PhpStorm.
 * User: phpNT - http://phpnt.com
 * Date: 05.07.2016
 * Time: 20:41
 */
/* @var $dataProvider yii\data\ActiveDataProvider */

namespace phpnt\exportFile\controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use yii\helpers\Json;
use yii\web\Controller;

class ExportController extends Controller
{
    public function actionExcel()
    {
        $data = $this->getData();
        $searchModel    = $data['searchModel'];
        $dataProvider   = $data['dataProvider'];
        $title          = $data['title'];
        $modelName      = $data['modelName'];
        $fields         = $this->getFieldsKeys($searchModel->exportFields());

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->setTitle($title ? $title : $modelName);
        $letter = 65;
        foreach ($fields as $one) {
            //$objPHPExcel->getActiveSheet()->getColumnDimension(chr($letter))->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension(chr($letter))->setAutoSize();
            $letter++;
        }
        $letter = 65;
        foreach ($fields as $one) {
            $objPHPExcel->getActiveSheet()->setCellValue(chr($letter).'1', $searchModel->getAttributeLabel($one));
            $objPHPExcel->getActiveSheet()->getStyle(chr($letter).'1')->getAlignment()->setHorizontal(
                \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $letter++;
        }
        $row = 2;
        $letter = 65;
        foreach ($dataProvider->getModels() as $model) {
                foreach ($searchModel->exportFields() as $one) {
                    if (is_string($one)) {
                        $objPHPExcel->getActiveSheet()->setCellValue(chr($letter).$row,$model[$one]);
                        $objPHPExcel->getActiveSheet()->getStyle(chr($letter).$row)->getAlignment()->setHorizontal(
                            \PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    } else {
                        $objPHPExcel->getActiveSheet()->setCellValue(chr($letter).$row,$one($model));
                        $objPHPExcel->getActiveSheet()->getStyle(chr($letter).$row)->getAlignment()->setHorizontal(
                            \PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                    }
                    $letter++;
                }
            $letter = 65;
            $row++ ;
        }

        header('Content-Type: application/vnd.ms-excel');
        $filename = $modelName.'_'.time().".xls";
        header('Content-Disposition: attachment;filename='.$filename);
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    public function actionCsv()
    {
        $data = $this->getData();
        $searchModel    = $data['searchModel'];
        $dataProvider   = $data['dataProvider'];
        $modelName      = $data['modelName'];
        $fields         = $this->getFieldsKeys($searchModel->exportFields());
        $csvCharset     = \Yii::$app->request->post('csvCharset');

        if ($csvCharset == 'Windows-1251') {
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv');
            $filename = $modelName.'_'.time().".csv";
            header('Content-Disposition: attachment;filename='.$filename);
            header('Content-Transfer-Encoding: binary');

            $fp = fopen('php://output', 'w');

            fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            if ($fp)
            {
                $items = [];
                $i = 0;
                foreach ($fields as $one) {
                    $items[$i] = iconv('utf-8', 'windows-1251', $searchModel->getAttributeLabel($one));
                    $i++;
                }
                fputcsv($fp, $items, ";");
                $items = [];
                $i = 0;
                foreach ($dataProvider->getModels() as $model) {
                    foreach ($searchModel->exportFields() as $one) {
                        if (is_string($one)) {
                            $items[$i] = iconv('utf-8', 'windows-1251', $model[$one]);
                        } else {
                            $items[$i] = iconv('utf-8', 'windows-1251', $one($model));
                        }
                        $i++;
                    }
                    fputcsv($fp, $items, ";");
                    $items = [];
                    $i = 0;
                }
            }
            fclose($fp);
        } else {
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv');
            $filename = $modelName.'_'.time().".csv";
            header('Content-Disposition: attachment;filename='.$filename);
            header('Content-Transfer-Encoding: binary');

            $fp = fopen('php://output', 'w');

            fputs($fp, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
            if ($fp)
            {
                $items = [];
                $i = 0;
                foreach ($fields as $one) {
                    $items[$i] = $searchModel->getAttributeLabel($one);
                    $i++;
                }
                fputcsv($fp, $items, ";");
                $items = [];
                $i = 0;
                foreach ($dataProvider->getModels() as $model) {
                    foreach ($searchModel->exportFields() as $one) {
                        if (is_string($one)) {
                            $items[$i] = $model[$one];
                        } else {
                            $items[$i] = $one($model);
                        }
                        $i++;
                    }
                    fputcsv($fp, $items, ";");
                    $items = [];
                    $i = 0;
                }
            }
            fclose($fp);
        }
    }

    public function actionWord()
    {
        $data = $this->getData();
        $searchModel    = $data['searchModel'];
        $dataProvider   = $data['dataProvider'];
        $title          = $data['title'];
        $modelName      = $data['modelName'];
        $fields         = $this->getFieldsKeys($searchModel->exportFields());

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $sectionStyle = $section->getSettings();
        $sectionStyle->setLandscape();
        $sectionStyle->setBorderTopColor('C0C0C0');
        $sectionStyle->setMarginTop(300);
        $sectionStyle->setMarginRight(300);
        $sectionStyle->setMarginBottom(300);
        $sectionStyle->setMarginLeft(300);
        $phpWord->addTitleStyle(1, ['name'=>'HelveticaNeueLT Std Med', 'size'=>16], ['align'=>'center']); //h
        $section->addTitle('<p style="font-size: 24px; text-align: center;">'.$title ? $title : $modelName.'</p>');

        $table = $section->addTable(
            [
                'name' => 'Tahoma',
                'align'=>'center',
                'cellMarginTop'     => 30,
                'cellMarginRight'   => 30,
                'cellMarginBottom'  => 30,
                'cellMarginLeft'    => 30,
            ]);
        $table->addRow(300, ['exactHeight' => true]);
        foreach ($fields as $one) {
            $table->addCell(1500,[
                'bgColor'           => 'eeeeee',
                'valign'            => 'center',
                'borderTopSize'     => 5,
                'borderRightSize'   => 5,
                'borderBottomSize'  => 5,
                'borderLeftSize'    => 5
            ])->addText($searchModel->getAttributeLabel($one),['bold'=>true, 'size' => 10], ['align'=>'center']);
        }
        foreach ($dataProvider->getModels() as $model) {
            $table->addRow(300, ['exactHeight' => true]);
            foreach ($searchModel->exportFields() as $one) {
                if (is_string($one)) {
                    $table->addCell(1500,[
                        'valign'            => 'center',
                        'borderTopSize'     => 1,
                        'borderRightSize'   => 1,
                        'borderBottomSize'  => 1,
                        'borderLeftSize'    => 1
                    ])->addText($model[$one],['bold'=>false, 'size' => 10], ['align'=>'right']);
                } else {
                    $table->addCell(1500,[
                        'valign'            => 'center',
                        'borderTopSize'     => 1,
                        'borderRightSize'   => 1,
                        'borderBottomSize'  => 1,
                        'borderLeftSize'    => 1
                    ])->addText($one($model),['bold'=>false, 'size' => 10], ['align'=>'right']);
                }
            }
        }

        header('Content-Type: application/vnd.ms-word');
        $filename = $modelName.'_'.time().".docx";
        header('Content-Disposition: attachment;filename='.$filename .' ');
        header('Cache-Control: max-age=0');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');
    }

    public function actionHtml()
    {
        $data = $this->getData();
        $searchModel    = $data['searchModel'];
        $dataProvider   = $data['dataProvider'];
        $title          = $data['title'];
        $modelName      = $data['modelName'];
        $fields         = $this->getFieldsKeys($searchModel->exportFields());

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle($title ? $title : $modelName);
        $table = $section->addTable(
            [
                'name' => 'Tahoma',
                'size' => 10,
                'align'=>'center',
            ]);
        $table->addRow(300, ['exactHeight' => true]);
        foreach ($fields as $one) {
            $table->addCell(1500,[
                'bgColor'           => 'eeeeee',
                'valign'            => 'center',
                'borderTopSize'     => 5,
                'borderRightSize'   => 5,
                'borderBottomSize'  => 5,
                'borderLeftSize'    => 5
            ])->addText($searchModel->getAttributeLabel($one),['bold'=>true, 'size' => 10], ['align'=>'center']);
        }
        foreach ($dataProvider->getModels() as $model) {
            $table->addRow(300, ['exactHeight' => true]);
            foreach ($searchModel->exportFields() as $one) {
                if (is_string($one)) {
                    $table->addCell(1500,[
                        'valign'            => 'center',
                        'borderTopSize'     => 1,
                        'borderRightSize'   => 1,
                        'borderBottomSize'  => 1,
                        'borderLeftSize'    => 1
                    ])->addText('<p style="margin-left: 10px;">'.$model[$one].'</p>',['bold'=>false, 'size' => 10], ['align' => 'right']);
                } else {
                    $table->addCell(1500,[
                        'valign'            => 'center',
                        'borderTopSize'     => 1,
                        'borderRightSize'   => 1,
                        'borderBottomSize'  => 1,
                        'borderLeftSize'    => 1
                    ])->addText('<p style="margin-left: 10px;">'.$one($model).'</p>',['bold'=>false, 'size' => 10], ['align' => 'right']);
                }
            }
        }

        header('Content-Type: application/html');
        $filename = $modelName.'_'.time().".html";
        header('Content-Disposition: attachment;filename='.$filename .' ');
        header('Cache-Control: max-age=0');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
        $objWriter->save('php://output');
    }

    public function actionPdf()
    {
        $data = $this->getData();
        $searchModel    = $data['searchModel'];
        $dataProvider   = $data['dataProvider'];
        $title          = $data['title'];
        $modelName      = $data['modelName'];
        $fields         = $this->getFieldsKeys($searchModel->exportFields());

        $options = new Options();
        $options->set('defaultFont', 'times');
        $dompdf = new Dompdf($options);
        $html = '<html><body>';
        $html .= '<h1>'.$title ? $title : $modelName.'</h1>';
        $html .= '<table width="100%" cellspacing="0" cellpadding="0">';
        $html .= '<tr style="background-color: #ececec;">';
        foreach ($fields as $one) {
            $html .= '<td style="border: 2px solid #cccccc; text-align: center; font-weight: 500;">'.$searchModel->getAttributeLabel($one).'</td>';
        }
        $html .= '</tr>';

        foreach ($dataProvider->getModels() as $model) {
            $html .= '<tr>';
            foreach ($searchModel->exportFields() as $one) {
                if (is_string($one)) {
                    $html .= '<td style="border: 1px solid #cccccc; text-align: left; font-weight: 300; padding-left: 10px;">'.$model[$one].'</td>';
                } else {
                    $html .= '<td style="border: 1px solid #cccccc; text-align: left; font-weight: 300; padding-left: 10px;">'.$one($model).'</td>';
                }
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</body></html>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream($modelName.'_'.time());
    }

    private function getData() {
        $searchAttributes = Json::decode(\Yii::$app->request->post('searchAttributes'));
        $searchModel = \Yii::$app->request->post('model');
        $array = explode("\\", $searchModel);
        $modelName = end($array);
        $searchAttributes = [
            $modelName => $searchAttributes
        ];
        \Yii::$app->request->queryParams = $searchAttributes;
        \Yii::$app->request->queryParams += ['sort' => \Yii::$app->request->post('sort')];
        \Yii::$app->request->queryParams += ['page' => \Yii::$app->request->post('page')];

        $searchModel = new $searchModel;
        $dataProvider = $searchModel->search($searchAttributes);
        $title = \Yii::$app->request->post('title');
        $getAll = \Yii::$app->request->post('getAll');
        if ($getAll) {
            $dataProvider->pagination = false;
        }

        return [
            'dataProvider'  => $dataProvider,
            'searchModel'   => $searchModel,
            'title'         => $title,
            'modelName'     => $modelName
        ];
    }

    private function getFieldsKeys($fieldsSended) {
        $fields = [];
        $i = 0;
        foreach ($fieldsSended as $key => $value) {
            if (is_int($key)) {
                $fields[$i] = $value;
            } else {
                $fields[$i] = $key;
            }
            $i++;
        }
        return $fields;
    }
}
