<?php

/*
 * 
 * Boleto Class 
 * Validation and short operations with Bank Slips
 * 
 * @package BoletoCoding
 * @author Anderson Gonçalves (Bônus) <contato@andersonsg.com.br>
 * @Version 1.0
 * 
 */

namespace BoletoCoding;

use \DateTime;
use \DateInterval;
use \Exception;

class Boleto {

    private $digitable_line;
    private $barcode;
    private $value = 0;
    private $due_date;
    private $valid;
    private $subtotal;
    
    /*
     * 
     * Constructor
     * 
     * @param String Digitable line.
     * @param Boolean If true, complete barcode with zero at right
     * 
     * @return Void
     * 
     */
    public function __construct($digitable_line, $auto_complete = true) {
        $this->digitable_line = $digitable_line;
        if ($auto_complete) {
            $this->auto_complete();
        }
        $this->calc_barcode();
        $this->valid = $this->validate();
    }
    
    /*
     * 
     * Complete barcode to minimum 47 digits
     * 
     * @return Void
     * 
     */
    private function auto_complete() {
        if (strlen($this->digitable_line) < 48 && strlen($this->digitable_line) !== 44) {
            while (strlen($this->digitable_line) !== 47) {
                $this->digitable_line .= "0";
            }
        }
    }
    
    /*
     * 
     * Transform digitable line in barcode
     * 
     * @return Void
     * 
     */
    private function calc_barcode() {
        $digitable_line_length = strlen($this->digitable_line);
        if ($digitable_line_length === 48) {
            $barcode = substr($this->digitable_line, 0, 11)
                    . substr($this->digitable_line, 12, 11)
                    . substr($this->digitable_line, 24, 11)
                    . substr($this->digitable_line, 36, 11);
        } elseif ($digitable_line_length === 47) {
            $barcode = substr($this->digitable_line, 0, 4)
                    . substr($this->digitable_line, 32, 1)
                    . substr($this->digitable_line, 33, 4)
                    . substr($this->digitable_line, 37, 10)
                    . substr($this->digitable_line, 4, 5)
                    . substr($this->digitable_line, 10, 10)
                    . substr($this->digitable_line, 21, 10);
        } else {
            $barcode = $this->digitable_line;
        }

        $this->barcode = $barcode;
    }

    /*
     * 
     * Validade barcode
     * 
     * @return Boolean
     * 
     */
    private function validate() {
        //Length barcode
        $barcode_length = strlen($this->digitable_line);
        if (!in_array($barcode_length, array(44, 47, 48))) {
            return false;
        }

        $valid = false;
        /*
         * 
         * Verifying digit
         * Barcode dealership start with "8" number and your verifying digit
         * is in position 3. In other cases, verifying digit is in position 4
         * 
         */
        $dv_position = substr($this->barcode, 0, 1) === "8" ? 3 : 4;
        
        /*
         * 
         * If value identifier of barcode dealership is "6" or "7", use calc module 10.
         * In other cases, use calc module 11
         * 
         */
        if (substr($this->barcode, 0, 1) === "8" && in_array(substr($this->barcode, 2, 1), array(6, 7))) {
            $valid = self::mod_10($this->barcode, $dv_position);
        } else {
            $valid = self::mod_11($this->barcode, $dv_position);
        }
        
        if ($valid) {
            $this->calc_value();
            $this->calc_due_date();
        }

        return $valid;
    }
    
    /*
     * 
     * Return if barcode is valid
     * 
     * @return Boolean
     *      
     */
    public function is_valid() {
        return $this->valid;
    }
    
    /*
     * 
     * Get barcode fragment corresponding to the value and convert to double
     * 
     * @return Void
     *      
     */
    public function calc_value() {
        $value = substr($this->barcode, 0, 1) === "8" ? substr($this->barcode, 4, 11) : substr($this->barcode, 9, 10);
        $value = (double) (substr($value, 0, strlen($value) - 2) . '.' . substr($value, strlen($value) - 2));
        $this->value = $this->subtotal = $value;
    }
    
    /*
     * 
     * Get barcode fragment corresponding to the due date and convert to date object.
     * If barcode is bank slip, fragment is number of days between 1997-10-07 and due date,
     * else fragment can have the format YYYYMMDD.
     * 
     * @return Void
     *      
     */
    public function calc_due_date() {
        $due_date = null;
        if (substr($this->barcode, 0, 1) === "8") {
            $year = (int) substr($this->barcode, 19, 4);
            $month = (int) substr($this->barcode, 23, 2);
            $day = (int) substr($this->barcode, 25, 2);
            if (checkdate($month, $day, $year)) {
                $due_date = new DateTime("$year-$month-$day");
            }
        } else {
            $fact = (int) substr($this->barcode, 5, 4);
            if ($fact >= 1000) {
                $due_date = new DateTime('1997-10-07');
                $due_date->add(new DateInterval("P" . $fact . "D"));
            }
        }
        $this->due_date = $due_date;
    }
    
    /*
     * 
     * Return value of document
     * 
     * @return Double
     *      
     */
    public function get_value() {
        return $this->value;
    }
    
    /*
     * 
     * Set document value only if value not detected in barcode
     * 
     * @param Double
     * 
     * @return Void
     *      
     */
    public function set_value($value) {
        if ($this->value !== 0) {
            throw new Exception("Value calculated by the bar code. Use discount or surcharge method.");
        }
        echo $this->value;
        $this->value = $this->subtotal = $value;
    }
    
    /*
     * 
     * Surcharge in document
     * 
     * @param Double
     * 
     * @return Void
     *      
     */
    public function surcharge($value) {
        $this->subtotal += $value;
    }
    
    /*
     * 
     * Discount in document
     * 
     * @param Double
     * 
     * @return Void
     *      
     */
    public function discount($value) {
        $this->subtotal -= $value;
    }
    
    /*
     * 
     * Return subtotal of document
     * 
     * @return Double
     *      
     */
    public function get_subtotal() {
        return $this->subtotal;
    }
    
    /*
     * 
     * Return due date of document
     * 
     * @param String - Date Format to return
     * 
     * @return Void
     *      
     */
    public function get_due_date($format = "Y-m-d") {
        return $this->due_date ? $this->due_date->format($format) : null;
    }
    
    /*
     * 
     * Calculate module 11 of verifying digit
     * 
     * @param String
     * @param Int - Position of verifying digit
     * 
     * @return Boolean - True if is valid.
     *      
     */
    public static function mod_11($barcode, $dv_position) {
        $barcode_aux = substr($barcode, 0, $dv_position) . substr($barcode, $dv_position + 1);
        $dv = (int) substr($barcode, $dv_position, 1);
        $sum = 0;
        for ($i = strlen($barcode_aux) - 1, $multiplier = 2; $i >= 0; $i--) {
            $sum += $barcode_aux[$i] * $multiplier;
            $multiplier++;
            if ($multiplier > 9) {
                $multiplier = 2;
            }
        }

        $dv_result = 11 - ($sum % 11);
        if ($dv_position === 4) {
            if (in_array($dv_result, array(0, 10, 11))) {
                $dv_result = 1;
            }
        } else {
            if (in_array($dv_result, array(0, 1))) {
                $dv_result = 0;
            } elseif (in_array($dv_result, array(10, 11))) {
                $dv_result = 1;
            }
        }

        return $dv == $dv_result;
    }
    
    /*
     * 
     * Calculate module 10 of verifying digit
     * 
     * @param String
     * @param Int - Position of verifying digit
     * 
     * @return Boolean - True if is valid.
     *      
     */
    public static function mod_10($barcode, $dv_position) {
        $barcode_aux = substr($barcode, 0, $dv_position) . substr($barcode, $dv_position + 1);
        $dv = (int) substr($barcode, $dv_position, 1);
        $sum = 0;
        for ($i = strlen($barcode_aux) - 1, $multiplier = 2; $i >= 0; $i--) {
            $multiplier_result = (string) ($barcode_aux[$i] * $multiplier);
            for ($pos = 0; $pos < strlen($multiplier_result); $pos++) {
                $sum += $multiplier_result[$pos];
            }
            $multiplier--;
            if ($multiplier < 1) {
                $multiplier = 2;
            }
        }

        $dv_result = $sum % 10;

        if ($dv_result !== 0) {
            $dv_result = 10 - $dv_result;
        }

        return $dv == $dv_result;
    }

}
