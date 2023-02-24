<?php

function CheckConst($splitted_const){
    switch($splitted_const[0]) {
        case 'bool':
            if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                return;
            }
            else {
                exit(23);
            }

        case 'int':
            if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                return;
            }
            else {
                exit(23);
            }

        case 'nil':
            if(!strcmp($splitted_const[1], "nil")){
                return;
            }
            else {
                exit(23);
            }

        case 'string':
            for($i = 0; $i < strlen($splitted_const[1]); $i++) {
                if($splitted_const[1][$i] == '\\') {
                    if($i + 3 < strlen($splitted_const[1])) {
                        if(!is_numeric($splitted_const[1][$i + 1]) || !is_numeric($splitted_const[1][$i + 2]) || !is_numeric($splitted_const[1][$i + 3])) {
                            exit(23);
                        }
                    } 
                    else {
                        exit(23);
                    }
                }
            }
            if(preg_match("/[0-9a-zA-Z!@%^&?\/]*/", $splitted_const[1])){
                return;
            }
            else {
                exit(23);
            }

        default:
            exit(23);
        }
}
class Line {
    public $func_name;
    public $arg1;
    public $arg2;
    public $arg3;
    public $number;
    public $order;
    
    public function ConveratationNoArgs() {
        if($this->number !== 1) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        echo ("\t</instruction>\n");
    }

    public function ConveratationVar() {
        if($this->number !== 2) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"var\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            exit(23);
        }
        echo ("\t</instruction>\n");
    }

    public function ConveratationSymb() {
        if($this->number !== 2) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"var\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg1, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg1 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg1>\n");
        }
        echo ("\t</instruction>\n");
    }

    public function ConveratationVarSymb() {
        if($this->number !== 3) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        
        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"var\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            exit(23);
        }

        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg2)){
            echo ("\t\t<arg2 type=\"var\">" . $this->arg2 . "</arg2>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg2, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg2 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg2>\n");
        }
        echo ("\t</instruction>\n");
    }

    public function ConveratationVarSymbSymb() {
        if($this->number !== 4) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"var\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            exit(23);
        }

        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg2)){
            echo ("\t\t<arg2 type=\"var\">" . $this->arg2 . "</arg2>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg2, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg2 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg2>\n");
        }

        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg3)){
            echo ("\t\t<arg3 type=\"var\">" . $this->arg3 . "</arg3>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg3, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg3 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg3>\n");
        }

        echo ("\t</instruction>\n");
    }

    public function ConveratationLabel() {
        if($this->number !== 2) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/@/", $this->arg1)) {
            exit(23);
        }
        if(preg_match("/\A[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"label\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            exit(23);
        }
        echo ("\t</instruction>\n");
    }

    public function ConveratationLabelSymbSymb() {
        if($this->number !== 4) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/@/", $this->arg1)) {
            exit(23);
        }
        if(preg_match("/\A[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"label\">" . $this->arg1 . "</arg1>\n");
        }

        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg2)){
            echo ("\t\t<arg2 type=\"var\">" . $this->arg2 . "</arg2>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg2, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg2 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg2>\n");
        }

        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg3)){
            echo ("\t\t<arg3 type=\"var\">" . $this->arg3 . "</arg3>\n");
        }
        else {
            $splitted_const = explode('@', $this->arg3, 2);
            CheckConst($splitted_const);
            echo ("\t\t<arg3 type=\"" . $splitted_const[0] . "\">" . $splitted_const[1] . "</arg3>\n");
        }

        echo ("\t</instruction>\n");
    }

    public function ConveratationVarType() {
        if($this->number !== 3) {
            exit(23);
        }
        echo ("\t<instruction order=\"" . $this->order . "\" opcode=\"" . strtoupper($this->func_name) . "\">\n");
        if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $this->arg1)){
            echo ("\t\t<arg1 type=\"var\">" . $this->arg1 . "</arg1>\n");
        }
        else {
            exit(23);
        }

        if($this->arg2 == "int" || $this->arg2 == "string" || $this->arg2 == "bool") {
            echo ("\t\t<arg2 type=\"type\">" . $this->arg2 . "</arg2>\n");
        }
        else {
            exit(23);
        }

        echo ("\t</instruction>\n");
    }
}



ini_set('display_errors', 'stderr');

/*if ($argc > 1) {
    if($argv[1] == "--help") {
        if($argc > 2) {
            exit(10);
        }
        echo("Usage: parse.php [options] <inputFileName\n");
        exit(0);
    }
    else {
        exit(10);
    }
}*/

$header = false;
$order = 1;
echo ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
for ($i = 0; $line = fgets(STDIN); $i++) {
    $starting_comment_arr = str_split($line);
    if($starting_comment_arr[0] == '#' || $starting_comment_arr[0] == "\n") {
        continue;
    }

    $splitted_line_hash = explode('#', $line); //deleting all the comments

    $line = $splitted_line_hash[0];

    $splitted_line_spaces = explode(' ', trim($line, "\n")); //seperating the string and deleting EOL
    
    $splitted_line_spaces = array_filter($splitted_line_spaces);
    $splitted_line_spaces = array_values($splitted_line_spaces);

    $splitted_line_spaces = preg_replace("/&/","&amp;",$splitted_line_spaces);
    $splitted_line_spaces = preg_replace("/</","&lt;",$splitted_line_spaces);
    $splitted_line_spaces = preg_replace("/>/","&gt;",$splitted_line_spaces);

    if(!$header) {
        if($splitted_line_spaces[0] == ".IPPcode23") {
            $header = true;
            echo ("<program language=\"IPPcode23\">\n");
            continue;
        }
        else {
            exit(21);
        }
    }
    
    $filteredLine = new Line();
    $filteredLine->func_name = $splitted_line_spaces[0];
    $filteredLine->order = $order;
    $filteredLine->number = count($splitted_line_spaces);
    switch($filteredLine->number) {
        case 1:
            break;
        case 2:
            $filteredLine->arg1 = $splitted_line_spaces[1];
            break;
        case 3:
            $filteredLine->arg1 = $splitted_line_spaces[1];
            $filteredLine->arg2 = $splitted_line_spaces[2];
            break;
        case 4:
            $filteredLine->arg1 = $splitted_line_spaces[1];
            $filteredLine->arg2 = $splitted_line_spaces[2];
            $filteredLine->arg3 = $splitted_line_spaces[3];
            break;
        default:
            exit(23);
    }

    switch(strtoupper($filteredLine->func_name)) {
        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            $filteredLine->ConveratationNoArgs();
            $order++;
            break;

        case 'DEFVAR':
        case 'POPS':
            $filteredLine->ConveratationVar();
            $order++;
            break;

        case 'PUSHS':
        case 'EXIT':
        case 'WRITE':
        case 'DPRINT':
            $filteredLine->ConveratationSymb();
            $order++;
            break;

        case 'MOVE':
        case 'TYPE':
        case 'INT2CHAR':
        case 'STRLEN':
        case 'NOT':
            $filteredLine->ConveratationVarSymb();
            $order++;
            break;

        case 'CALL':
        case 'LABEL':
        case 'JUMP':
            $filteredLine->ConveratationLabel();
            $order++;
            break;

        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            $filteredLine->ConveratationLabelSymbSymb();
            $order++;
            break;

        case 'ADD':
        case 'SUB':
        case 'MUL':
        case 'IDIV':
        case 'LT':
        case 'GT':
        case 'EQ':
        case 'AND':
        case 'OR':
        case 'STRI2INT':
        case 'GETCHAR':
        case 'CONCAT':
        case 'SETCHAR':
            $filteredLine->ConveratationVarSymbSymb();
            $order++;
            break;

        case 'READ':
            $filteredLine->ConveratationVarType();
            $order++;
            break;

        default:
            exit(22);
    }

}
echo ("</program>\n");
?>