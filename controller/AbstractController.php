<?php 

 abstract class AbstractController {
    protected $temp = array();
    protected $mode = '';
    protected $inputData;
    
    public function init()
    {
        $this->inputData = json_decode(file_get_contents("php://input"), TRUE);
        return array();
    }

    public function __get($name) 
    {
        if (!isset($this->temp[$name])) {
            switch ($name) {
                case 'db':
                    $this->temp['db'] = Db::getDbInstance();
                    break;
                case 'model':
                    $this->temp['model'] = new Model();
                    break;
                default :
                    $pluginName = ucfirst($name);
                    $this->temp[$name] = new $pluginName();
            }
        }
        return $this->temp[$name];
    }
}