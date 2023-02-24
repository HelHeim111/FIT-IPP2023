<?php
ini_set('display_errors', 'stderr');

if ($argc > 1) {
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
}

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

    /*$splitted_line_spaces = array_filter($splitted_line_spaces, function ($data) {
        return $data !== '';
    });*/
    
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
                echo("hi\n");
            exit(21);
        }
    }
    //converting into XML code
    switch(strtoupper($splitted_line_spaces[0])) {
        case 'CREATEFRAME':
        case 'PUSHFRAME':
        case 'POPFRAME':
        case 'RETURN':
        case 'BREAK':
            if(count($splitted_line_spaces) !== 1) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'DEFVAR':
        case 'POPS':
            if(count($splitted_line_spaces) !== 2) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$&%*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }
            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'PUSHS':
        case 'EXIT':
        case 'WRITE':
        case 'DPRINT':
            if(count($splitted_line_spaces) !== 2) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[1], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg1 type=\"bool\">" . $splitted_const[1] . "</arg1>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg1 type=\"int\">" . $splitted_const[1] . "</arg1>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg1 type=\"nil\">" . $splitted_const[1] . "</arg1>\n");
                            break;
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
                            echo ("\t\t<arg1 type=\"string\">" . $splitted_const[1] . "</arg1>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }
            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'MOVE':
        case 'TYPE':
            if(count($splitted_line_spaces) !== 3) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }
            echo ("\t</instruction>\n");
            $order++;
            break;
        
        case 'INT2CHAR':
            if(count($splitted_line_spaces) !== 3) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }
                    default:
                        exit(23);
                }
            }
            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'STRLEN':
            if(count($splitted_line_spaces) !== 3) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }
            echo ("\t</instruction>\n");
            $order++;
            break;
        
        case 'CALL':
        case 'LABEL':
        case 'JUMP':
            if(count($splitted_line_spaces) !== 2) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/@/", $splitted_line_spaces[1]) || count($splitted_line_spaces) !== 2) {
                exit(23);
            }
            if(preg_match("/\A[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"label\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }
            echo ("\t</instruction>\n");
            break;

        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/@/", $splitted_line_spaces[1]) || count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            if(preg_match("/\A[a-zA-Z_\-$&%*!?][a-zA-Z_\-$&%*!?0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"label\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'ADD':
        case 'SUB':
        case 'MUL':
        case 'IDIV':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }


            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'LT':
        case 'GT':
        case 'EQ':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%*&\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;


        case 'AND':
        case 'OR':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'NOT':
            if(count($splitted_line_spaces) !== 3) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }
            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'STRI2INT':
        case 'GETCHAR':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%\-&*?!][a-zA-Z_$%&*?\-!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }
            

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$\-%&*?!][a-zA-Z_$%\-&*?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {

                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }


                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'READ':
            if(count($splitted_line_spaces) !== 3) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if($splitted_line_spaces[2] == "int" || $splitted_line_spaces[2] == "string" || $splitted_line_spaces[2] == "bool") {
                echo ("\t\t<arg2 type=\"type\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                exit(23);
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        case 'CONCAT':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*?!\-0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%\-&*?!][a-zA-Z_$%&*?\-!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%&*\-?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;
            
        case 'SETCHAR':
            if(count($splitted_line_spaces) !== 4) {
                exit(23);
            }
            echo ("\t<instruction order=\"" . $order . "\" opcode=\"" . strtoupper($splitted_line_spaces[0]) . "\">\n");
            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%\-&*?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[1])){
                echo ("\t\t<arg1 type=\"var\">" . $splitted_line_spaces[1] . "</arg1>\n");
            }
            else {
                exit(23);
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%\-&*?!][a-zA-Z_$%\-&*?!0-9]*/", $splitted_line_spaces[2])){
                echo ("\t\t<arg2 type=\"var\">" . $splitted_line_spaces[2] . "</arg2>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[2], 2);

                switch($splitted_const[0]) {

                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg2 type=\"bool\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg2 type=\"int\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg2 type=\"nil\">" . $splitted_const[1] . "</arg2>\n");
                            break;
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
                            echo ("\t\t<arg2 type=\"string\">" . $splitted_const[1] . "</arg2>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }


                    default:
                        exit(23);
                }
            }

            if(preg_match("/(LF|GF|TF)@[a-zA-Z_$%\-&*?!][a-zA-Z_$%&*\-?!0-9]*/", $splitted_line_spaces[3])){
                echo ("\t\t<arg3 type=\"var\">" . $splitted_line_spaces[3] . "</arg3>\n");
            }
            else {
                $splitted_const = explode('@', $splitted_line_spaces[3], 2);

                switch($splitted_const[0]) {
                    case 'bool':
                        if(!strcmp($splitted_const[1], "true") || !strcmp($splitted_const[1], "false")) {
                            echo ("\t\t<arg3 type=\"bool\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'int':
                        if(preg_match("/[0-9][0-9]*/", $splitted_const[1])){
                            echo ("\t\t<arg3 type=\"int\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    case 'nil':
                        if(!strcmp($splitted_const[1], "nil")){
                            echo ("\t\t<arg3 type=\"nil\">" . $splitted_const[1] . "</arg3>\n");
                            break;
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
                            echo ("\t\t<arg3 type=\"string\">" . $splitted_const[1] . "</arg3>\n");
                            break;
                        }
                        else {
                            exit(23);
                        }

                    default:
                        exit(23);
                }
            }

            echo ("\t</instruction>\n");
            $order++;
            break;

        default:
            exit(22);
    }

}
echo ("</program>\n");

?>