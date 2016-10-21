<?php

namespace Cewi\Excel\View;

use Cake\Core\Exception\Exception;
use Cake\Event\EventManager;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Cake\View\View;

/**
 * @package  Cake.View
 */
class ExcelView extends View
{

    /**
     * PHPExcel instance
     *
     * @var PhpExcel
     */
    public $PHPExcel = null;

    /**
     * Filename
     *
     * @var string
     */
    private $__filename;

    /**
     * The subdirectory.  Excel views are always in xlsx.
     * 
     * @var string
     */
    public $subDir = 'xlsx';

    /**
     * pointer to the active sheet in the workbook
     *
     * @var integer
     */
    public $currentSheetIndex = null;

    /**
     * Constructor
     *
     * @param \Cake\Network\Request $request Request instance.
     * @param \Cake\Network\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     *
     * @throws \Cake\Core\Exception\Exception
     */
    public function __construct(Request $request = null, Response $response = null, EventManager $eventManager = null, array $viewOptions = [])
    {
        parent::__construct($request, $response, $eventManager, $viewOptions);

        if (isset($viewOptions['templatePath']) && $viewOptions['templatePath'] == 'Error') {
            $this->layoutPath = null;
            $this->subDir = null;
            $response->type('html');

            return;
        }

        // intitialize PHPExcel-Object
        \PHPExcel_Cell::setValueBinder(new \PHPExcel_Cell_AdvancedValueBinder());
        $this->PHPExcel = new \PHPExcel();

        $this->currentSheetIndex = 0;
    }

    /**
     * Initialization hook method.
     * Load the Helper
     */
    public function initialize()
    {
        parent::initialize();
        $this->layout('default');
        $this->loadHelper('Cewi/Excel.Excel');
    }
    
    /**
     * [render description]
     * 
     * @param  [type] $action [description]
     * @param  [type] $layout [description]
     * @param  [type] $file   [description]
     * @return [type]         [description]
     */
    public function render($action = null, $layout = null, $file = null)
    {
        $content = parent::render($action, false, $file);
        if ($this->response->type() == 'text/html') {
            return $content;
        }

        $content = $this->__output();
        $this->Blocks->set('content', $content);

        $this->response->download($this->getFilename());

        return $this->Blocks->get('content');
    }

    /**
     * Generates the binary excel data
     *
     * @todo find a way to set date format for generated cells
     * @return string
     * @throws CakeException If the excel writer does not exist
     */
    private function __output()
    {

        //remove initially created empty Sheet
        $this->currentSheetIndex = $this->PHPExcel->getIndex($this->PHPExcel->getSheetByName('Worksheet'));
        $this->PHPExcel->removeSheetByIndex($this->currentSheetIndex);

        $this->PHPExcel->getProperties()->setCreator(Configure::read('App.author'));
        $this->PHPExcel->getProperties()->setDescription('generated by ' . Configure::read('App.title'));
        $this->PHPExcel->getProperties()->setKeywords("office 2007 openxml php");

        ob_start();

        $writer = \PHPExcel_IOFactory::createWriter($this->PHPExcel, 'Excel2007');

        if (!isset($writer)) {
            throw new Exception('Excel writer not found');
        }

        $writer->setPreCalculateFormulas(false);
        $writer->setIncludeCharts(true);
        $writer->save('php://output');

        $output = ob_get_clean();

        return $output;
    }

    /**
     * Set the Name of Excel-File
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->__filename = $filename;
    }

    /**
     * get the Name of Excel-File
     *
     * @return string
     */
    public function getFilename()
    {
        if (!empty($this->__filename)) {
            return $this->__filename . '.xlsx';
        }
        return Inflector::slug($this->request->url) . '.xlsx';
    }

}
