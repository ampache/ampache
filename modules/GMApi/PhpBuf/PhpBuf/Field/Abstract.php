<?php
/**
 * @author Andrey Lepeshkin (lilipoper@gmail.com)
 * @link http://github.com/undr/phpbuf
 *
 */
abstract class PhpBuf_Field_Abstract implements PhpBuf_Field_Interface {
    
    /**
     * Value of field
     *
     * @var mixed
     */
    protected $value = null;
    /**
     * Additional information for field.
     * If field has enum type, then extra contain array of enumerable values
     * If field has message type, then extra contain name of message class as string
     *
     * @var mixed
     */
    protected $extra;
    /**
     * Has 1, 2 or 3. PhpBuf_Rule::REQUIRED, PhpBuf_Rule::OPTIONAL, PhpBuf_Rule::REPEATED
     *
     * @var integer
     */
    protected $rule;
    /**
     * Index of field tag
     *
     * @var integer
     */
    protected $index;
    /**
     * Wire type(PhpBuf_WireType)
     *
     * @var integer
     */
    protected $wireType;

    /**
     * Fabric method, create classes extended from PhpBuf_Field_Abstract
     *
     * @param string $type
     * @param array $args
     * @return PhpBuf_Field_Abstract
     */
    public static function create($type, $args) {
        $class = 'PhpBuf_Field_' . PhpBuf_Type::getNameById($type);
        if(!class_exists($class)) {
            throw new PhpBuf_Field_Exception("field '$class' not found");
        }
        return new $class($args['index'], $args['rule'], $args['extra']);
    }
    /**
     * Constructor. Возможно его нужно закрыть
     *
     * @param integer $index
     * @param integer $rule
     * @param mixed $extra
     */
    public function __construct($index, $rule, $extra) {
        $this->index = $index;
        $this->rule = $rule;
        $this->extra = $extra;
    }
    /**
     * To set value of field
     *
     * @param mixed $value
     */
    public function setValue($value) {
        if(!$this->checkTypeOfValue($value)) {
            throw new PhpBuf_Field_Exception("wrong type of value (value type: " . gettype($value) . ")");
        }
        $this->value = $value;
    }
    /**
     * To get value of field
     *
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    public function getRule(){
        return $this->rule;
    }
    public function getExtra(){
        return $this->extra;
    }
    /**
     * Read field from reader
     *
     * @param PhpBuf_IO_Reader_Interface $reader
     */
    public function read(PhpBuf_IO_Reader_Interface $reader) {
        if(PhpBuf_Rule::REPEATED === $this->rule) {
            $this->value[] = $this->readImpl($reader);
        } else {
            $this->value = $this->readImpl($reader);
        }
    }
    /**
     * Write field to writer
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     */
    public function write(PhpBuf_IO_Writer_Interface $writer) {
        if(PhpBuf_Rule::OPTIONAL === $this->rule && null === $this->value) {
            return;
        }
        
        if(PhpBuf_Rule::REPEATED === $this->rule) {
            if(null === $this->value) {
                return;
            }
            foreach ($this->value as $item) {
                $this->writeHeader($writer);
                $this->writeImpl($writer, $item);
            }
        } else {
            $this->writeHeader($writer);
            $this->writeImpl($writer, $this->value);
        }
    }
    /**
     * Enter description here...
     *
     * @return integer
     */
    public function getWireType() {
        /**
         * return $this->wireType; not work, returned null. May by php bug?
         */
        $wt = $this->wireType;
        return $wt;
    }
    
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Reader_Interface $reader
     */
    protected function readImpl(PhpBuf_IO_Reader_Interface $reader) {
        throw new PhpBuf_Field_Exception("you mast override function PhpBuf_Field_Abstract#readImpl");
    }
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     */
    protected function writeImpl(PhpBuf_IO_Writer_Interface $writer, $value) {
        throw new PhpBuf_Field_Exception("you mast override function PhpBuf_Field_Abstract#writeImpl");
    }
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Reader_Interface $reader
     * @return mixed
     */
    protected function readWireTypeData(PhpBuf_IO_Reader_Interface $reader) {
        //
        // extremely low memory condition [and/or] no class loaded(using autoload): crash calling call_user_func_array
        //
        if(PhpBuf_WireType::WIRETYPE_LENGTH_DELIMITED === $this->wireType){
            return PhpBuf_WireType_LenghtDelimited::read($reader);
        }
        return call_user_func_array(array('PhpBuf_WireType_' . PhpBuf_WireType::getWireTypeNameById($this->wireType), 'read'), array($reader));
    }
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     * @param mixed $value
     */
    protected function writeWireTypeData(PhpBuf_IO_Writer_Interface $writer, $value) {
        //
        // extremely low memory condition [and/or] no class loaded(using autoload): crash calling call_user_func_array
        //
        if(PhpBuf_WireType::WIRETYPE_LENGTH_DELIMITED === $this->wireType){
            PhpBuf_WireType_LenghtDelimited::write($writer, $value);
        } else {
            call_user_func_array(array('PhpBuf_WireType_' . PhpBuf_WireType::getWireTypeNameById($this->wireType), 'write'), array($writer, $value));
        }
    }
    /**
     * Enter description here...
     *
     * @param mixed $value
     * @return boolean
     */
    protected function checkTypeOfValue($value) {
        if(PhpBuf_Rule::REPEATED === $this->rule && !is_array($value)) {
            return false;
        }
        if(PhpBuf_Rule::REPEATED === $this->rule) {
            foreach ($value as $item) {
                if(!$this->checkTypeOfValueImpl($item)) {
                    return false;
                }
            }
            return true;
        }
        return $this->checkTypeOfValueImpl($value);
    }
    /**
     * Enter description here...
     *
     * @param mixed $value
     */
    protected function checkTypeOfValueImpl($value) {
        throw new PhpBuf_Field_Exception("you mast override function PhpBuf_Field_Abstract#checkTypeOfValueImpl");
    }
    /**
     * Enter description here...
     *
     * @param PhpBuf_IO_Writer_Interface $writer
     */
    protected function writeHeader(PhpBuf_IO_Writer_Interface $writer) {
        $value = $this->index << 3;
        $value = $value | $this->wireType;
        PhpBuf_Base128::encodeToWriter($writer, $value);
    }
}

