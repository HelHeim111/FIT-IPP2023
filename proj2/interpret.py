import re
import sys
import xml.etree.ElementTree as ET

#class for instructions
class Instruction:
    #list for all objects of instruction class
    listOfInstructions = []
    def __init__(self, name, order):
        self._name: str = name.upper()
        try:
            self._order: int = int(order)
        except:
            exit(32)
        self._args: Argument = []
        self.listOfInstructions.append(self)

    def add_argument(self, arg_type, value, frame):
        self._args.append(Argument(arg_type, value, frame))

    def get_name(self):
        return self._name
    
    def get_args(self):
        return self._args

    def get_order(self):
        return self._order
    
#class for arguments of instructions
class Argument:
    def __init__(self, arg_type, value, frame):
        self._arg_type = arg_type
        self._value = value
        self._frame = frame

    def get_frame(self):
        return self._frame

    def get_arg_type(self):
        return self._arg_type

    def get_arg_value(self):
        return self._value

g_frame = {}            #global frame
source_file = None      #source
input_file = None       #input
t_frame = None          #temporary frame
l_frame = None          #local frame
stack_frame = []        #stack of frames
stack_call = []         #call stack
stack = []              #stack
defined_labels = {}     #all the labels from source code
read_count = 0          #counter for read instruction, to   

possible_opcodes = ["MOVE", "CREATEFRAME", "PUSHFRAME", "POPFRAME", "DEFVAR", "CALL", "RETURN",
                    "PUSHS", "POPS",
                    "ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "INT2CHAR", "STRI2INT",
                    "READ", "WRITE",
                    "CONCAT", "STRLEN", "GETCHAR", "SETCHAR",
                    "TYPE", "LABEL", "JUMP", "JUMPIFEQ", "JUMPIFNEQ", "EXIT", "DPRINT", "BREAK"]

possible_types = ["int", "string", "nil", "bool", "label", "var", "type"]

def mySort(instructions:Instruction):
    n = len(instructions)
    done = True
    for x in range(n - 1):
        for y in range(n - 1 - x):
            if(instructions[y].get_order() <= 0) or (instructions[y].get_order()) == (instructions[y + 1].get_order()) :
                exit(32)
            if (instructions[y].get_order()) > (instructions[y + 1].get_order()) :
                done = False
                instructions[y], instructions[y+1] = instructions[y+1], instructions[y]
        if done:
            return
        
def check_arg_amount(expected: int, inst: Instruction):
    if len(inst.get_args()) != expected:
        exit(32)

def check_var_existance(var:Argument, g_frame, l_frame, t_frame):
    frame = var.get_frame()
    if frame == "GF":
        if var.get_arg_value() in g_frame:
            return True
    elif frame == "LF":
        if l_frame == None:
            exit(55)
        if var.get_arg_value() in l_frame:
            return True
    elif frame == "TF":
        if t_frame == None:
            exit(55)
        if var.get_arg_value() in t_frame:
            return True
    else:
        exit(52)
    return False
    
def check_var_value(var:Argument, g_frame, l_frame, t_frame):
    frame = var.get_frame()
    if frame == "GF":
        return g_frame.get(var.get_arg_value())
            
    if frame == "LF":
        if l_frame == None:
            exit(55)
        return l_frame.get(var.get_arg_value())
            
    if frame == "TF":
        if t_frame == None:
            exit(55)
        return t_frame.get(var.get_arg_value())
            
    return False

def check_var_type(val):
    try:
        int(val)
    except:
        if val in ["true", "false"]:
            return "bool"
        elif val == "nil":
            return "nil"
        else:
            return "string"
    else:
        return "int"

def control_escapeseq(string:str):
    if not isinstance(string, str):
        return
    seq = re.findall(r"\\\d\d\d", string)
    for s in seq:
        try:
            char = chr(int(s.lstrip("\\")))
        except:
            exit(53)
        else:
            string = string.replace(s, char)
    return string

def find_inst_by_order(order:int):
    counter = 0
    for inst in Instruction.listOfInstructions:
        if inst.get_order() == order:
            return counter
        counter += 1
def add_value_to_var(var:Argument,val, g_frame, l_frame, t_frame):
    frame = var.get_frame()
    if frame == "GF":
        g_frame[var.get_arg_value()] = val
    if frame == "LF":
        l_frame[var.get_arg_value()] = val
    if frame == "TF":
        t_frame[var.get_arg_value()] = val
    return var

def check_symb_type(var:Argument):
    typ = var.get_arg_type()
    if typ != "string" and typ != "int" and typ != "bool" and typ != "nil" and typ != "var":
        exit(53)
    if typ == "var":
        if not check_var_existance(var, g_frame, l_frame, t_frame):
            exit(54)
        

#Function will find declared labels before the main analysis begins 
def fisrt_walktrough(instructions:Instruction):
    for i in instructions:
        if i.get_name() == "LABEL":
            check_arg_amount(1, i)
            if i.get_args()[0].get_arg_type() != "label":
                exit(52)
            if i.get_args()[0].get_arg_value() not in defined_labels:
                defined_labels[i.get_args()[0].get_arg_value()] = i.get_order()
            else:
                exit(52)
#Function helps with arithmetical instructions
def arithmetic(args:Argument, g_frame, l_frame, t_frame):
    if args[0].get_arg_type() != "var" or args[1].get_arg_type() not in ["int", "var"] or args[2].get_arg_type() not in ["int", "var"]:
        exit(53)
    if not check_var_existance(args[0], g_frame, l_frame, t_frame):
        exit(54)
    if args[1].get_arg_type() == "var":
        val = check_var_value(args[1], g_frame, l_frame, t_frame)
        if val == None:
            exit(56)
        typ = check_var_type(val)
        if typ != "int":
            exit(53)
    if args[2].get_arg_type() == "var":
        val = check_var_value(args[2], g_frame, l_frame, t_frame)
        if val == None:
            exit(56)
        typ = check_var_type(val)
        if typ != "int":
            exit(53)

def interpret(instructions:Instruction):
    global l_frame
    global t_frame
    global g_frame
    global read_count
    
    for i in instructions:
        args = i.get_args()
        if i.get_name() == "CREATEFRAME":
            check_arg_amount(0, i)
            t_frame = {}

        elif i.get_name() == "PUSHFRAME":
            check_arg_amount(0, i)
            if t_frame == None:
                exit(55)
            stack_frame.append(t_frame)
            l_frame = stack_frame[len(stack_frame) - 1]
            t_frame = None

        elif i.get_name() == "POPFRAME":
            check_arg_amount(0, i)
            if l_frame is None:
                exit(55)
            t_frame = stack_frame.pop()
            if len(stack_frame) == 0:
                l_frame = None
            else:
                l_frame = stack_frame[len(stack_frame) - 1]

        elif i.get_name() == "MOVE":
            check_arg_amount(2, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            check_symb_type(args[1])
            arg1_val = args[1].get_arg_value()
            arg1_typ = args[1].get_arg_type()
            if arg1_typ == "var":
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                arg1_typ = check_var_type(arg1_val)
        
            args[0] = add_value_to_var(args[0], arg1_val, g_frame, l_frame, t_frame)
            

        elif i.get_name() == "DEFVAR":
            check_arg_amount(1, i)
            if check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(52)
            add_value_to_var(args[0], None, g_frame, l_frame, t_frame)

        elif i.get_name() == "CALL":
            check_arg_amount(1, i)
            stack_call.append(i.get_order() + 1)
            arg0_val = args[0].get_arg_value()
            if args[0].get_arg_type() != "label":
                exit(53)
            if arg0_val not in defined_labels:
                exit(52)
            
            order = defined_labels[arg0_val]
            place = find_inst_by_order(int(order))
            interpret(Instruction.listOfInstructions[place:])
            break

        elif i.get_name() == "RETURN":
            check_arg_amount(0, i)
            if len(stack_call) == 0:
                exit(56)
            order = stack_call.pop()
            place = find_inst_by_order(int(order))
            interpret(Instruction.listOfInstructions[place:])
            break

        elif i.get_name() == "PUSHS":
            check_arg_amount(1, i)
            check_symb_type(args[0])
            arg1_val = args[0].get_arg_value()
            if args[0].get_arg_type() == "var":
                arg1_val = check_var_value(args[0], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
            stack.append(arg1_val)
        
        elif i.get_name() == "POPS":
            check_arg_amount(1, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            if len(stack) == 0:
                exit(56)
            result = stack.pop()
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "ADD":
            check_arg_amount(3, i)
            arithmetic(args, g_frame, l_frame, t_frame)
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if args[1].get_arg_type() == "var":
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
            if args[2].get_arg_type() == "var":
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
            try:
                num1 = int(arg1_val)
            except:
                exit(32)
            try:
                num2 = int(arg2_val)
            except:
                exit(32)
            result = num1 + num2
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "SUB":
            check_arg_amount(3, i)
            arithmetic(args, g_frame, l_frame, t_frame)
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if args[1].get_arg_type() == "var":
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
            if args[2].get_arg_type() == "var":
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
            try:
                num1 = int(arg1_val)
            except:
                exit(32)
            try:
                num2 = int(arg2_val)
            except:
                exit(32)
            result = num1 - num2
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "MUL":
            check_arg_amount(3, i)
            arithmetic(args, g_frame, l_frame, t_frame)
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if args[1].get_arg_type() == "var":
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
            if args[2].get_arg_type() == "var":
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
            try:
                num1 = int(arg1_val)
            except:
                exit(32)
            try:
                num2 = int(arg2_val)
            except:
                exit(32)
            result = num1 * num2
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "IDIV":
            check_arg_amount(3, i)
            arithmetic(args, g_frame, l_frame, t_frame)
            if args[2].get_arg_value() == "0":
                exit(57)
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if args[1].get_arg_type() == "var":
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
            if args[2].get_arg_type() == "var":
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
            try:
                num1 = int(arg1_val)
            except:
                exit(32)
            try:
                num2 = int(arg2_val)
            except:
                exit(32)
            result = num1 / num2
            
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "LT":
            check_arg_amount(3, i)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if args[0].get_arg_type() != "var" or typ1 == "nil" or typ2 == "nil" or typ1 != typ2:
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            if typ1 == "int" and typ2 == "int":
                try:
                    arg1_val = int(arg1_val)
                except:
                    exit(53)
                try:
                    arg2_val = int(arg2_val)
                except:
                    exit(53)
            result = (arg1_val < arg2_val)
            if result == True:
                result = "true"
            else:
                result = "false"
            
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "GT":
            check_arg_amount(3, i)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val) 
            if args[0].get_arg_type() != "var" or typ1 == "nil" or typ2 == "nil" or typ1 != typ2:
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            if typ1 == "int" and typ2 == "int":
                try:
                    arg1_val = int(arg1_val)
                except:
                    exit(53)
                try:
                    arg2_val = int(arg2_val)
                except:
                    exit(53)
            result = (arg1_val > arg2_val)
            if result == True:
                result = "true"
            else:
                result = "false"

            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "EQ":
            check_arg_amount(3, i)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            if typ1 == "nil":
                if typ2 == "nil":
                    add_value_to_var(args[0], "true", g_frame, l_frame, t_frame)
                elif typ2 != "nil":
                    add_value_to_var(args[0], "false", g_frame, l_frame, t_frame)
                continue
            if typ2 == "nil":
                if typ1 == "nil":
                    add_value_to_var(args[0], "true", g_frame, l_frame, t_frame)
                elif typ1 != "nil":
                    add_value_to_var(args[0], "false", g_frame, l_frame, t_frame)
                continue
            if typ1 != typ2:
                exit(53)
            if typ1 == "int" and typ2 == "int":
                try:
                    arg1_val = int(arg1_val)
                except:
                    exit(53)
                try:
                    arg2_val = int(arg2_val)
                except:
                    exit(53)
            result = (arg1_val == arg2_val)
            if result == True:
                result = "true"
            else:
                result = "false"
                
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "AND":
            check_arg_amount(3, i)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if args[0].get_arg_type() != "var" or typ1 != "bool" or typ2 != "bool":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)  

            if arg1_val == "true" and arg2_val == "true":
                result = "true"
            else:
                result = "false"

            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "OR":
            check_arg_amount(3, i)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if args[0].get_arg_type() != "var" or typ1 != "bool" or typ2 != "bool":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)  

            if arg1_val == "false" and arg2_val == "false":
                result = "false"
            else:
                result = "true"

            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "NOT":
            check_arg_amount(2, i)
            typ1 = args[1].get_arg_type()
            arg1_val = args[1].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)

            if args[0].get_arg_type() != "var" or typ1 != "bool":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)  

            if arg1_val == "true":
                result = "false"
            else:
                result = "true"
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "INT2CHAR":
            check_arg_amount(2, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            arg1_val = args[1].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ1 != "int":
                exit(53)
            try:
                arg1_val = int(arg1_val)
            except:
                exit(53)
            try:
                result = chr(arg1_val)
            except:
                exit(58)
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "STRI2INT":
            check_arg_amount(3, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()

            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if typ1 != "string" or typ2 != "int":
                exit(53)
            if len(arg1_val) <= int(arg2_val):
                exit(58)

            result = ord(arg1_val[int(arg2_val)])

            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "READ":
            check_arg_amount(2, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            if args[1].get_arg_type() != "type" or args[1].get_arg_value() not in ["string", "int", "bool"]:
                exit(32)
            input_str = None
            if input_file == sys.stdin:
                try:
                    input_str = input()
                except:
                    add_value_to_var(args[0], "nil", g_frame, l_frame, t_frame)
                    continue
            else:
                f = open(input_file, "r")
                input_lines = []
                for line in f:
                    input_lines.append(line.rstrip('\n'))
                f.close()
                try:
                    input_str = input_lines[read_count]
                    read_count += 1
                except:
                    add_value_to_var(args[0], "nil", g_frame, l_frame, t_frame)
                    continue
            if args[1].get_arg_value() == "int":
                try:
                    int(input_str)
                except:
                    add_value_to_var(args[0], "nil", g_frame, l_frame, t_frame)
                    continue
            elif args[1].get_arg_value() == "bool":
                if input_str != "true":
                    input_str = "false"

            add_value_to_var(args[0], input_str, g_frame, l_frame, t_frame)

        elif i.get_name() == "WRITE":
            check_arg_amount(1, i)
            check_symb_type(args[0])
            output_val = args[0].get_arg_value()
            typ = args[0].get_arg_type()
            if typ == "var":
                val = check_var_value(args[0], g_frame, l_frame, t_frame)
                typ = check_var_type(val)
                output_val = val
            
            if typ == "nil":
                output_val = ""
            elif typ == "string":
                output_val = control_escapeseq(output_val)
            
            print(output_val,end='')

        elif i.get_name() == "CONCAT":
            check_arg_amount(3, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if typ1 != "string" or typ2 != "string":
                exit(53)
            result = arg1_val + arg2_val
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "STRLEN":
            check_arg_amount(2, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            arg1_val = args[1].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ1 != "string":
                exit(53)
            result = str(len(arg1_val))
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "GETCHAR":
            check_arg_amount(3, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()

            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if typ1 != "string" or typ2 != "int":
                exit(53)
            if len(arg1_val) <= int(arg2_val):
                exit(58)
            result = arg1_val[int(arg2_val)]
            add_value_to_var(args[0], result, g_frame, l_frame, t_frame)

        elif i.get_name() == "SETCHAR":
            check_arg_amount(3, i)
            typ0 = args[0].get_arg_type()
            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg0_val = args[2].get_arg_value()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()
            if typ0 != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            arg0_val = check_var_value(args[0], g_frame, l_frame, t_frame)
            if arg1_val == None:
                exit(56)
            typ0 = check_var_type(arg0_val)
            if typ0 != "string":
                exit(53)

            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(58)
                typ2 = check_var_type(arg2_val)
            if typ1 != "int" or typ2 != "string":
                exit(53)
            if len(arg0_val) <= int(arg1_val):
                exit(58)
            arg0_val = arg0_val[:int(arg1_val)] + arg2_val + arg0_val[int(arg1_val) + 1:]
            add_value_to_var(args[0], arg0_val, g_frame, l_frame, t_frame)
            

        elif i.get_name() == "TYPE":
            check_arg_amount(2, i)
            if args[0].get_arg_type() != "var":
                exit(53)
            if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                exit(54)
            typ1 = args[1].get_arg_type()
            arg1_val = args[1].get_arg_value()
            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                typ1 = check_var_type(arg1_val)
                if arg1_val == None:
                    typ1 = ""
            add_value_to_var(args[0], typ1, g_frame, l_frame, t_frame)

        elif i.get_name() == "LABEL":
            continue

        elif i.get_name() == "JUMP":
            check_arg_amount(1, i)
            arg0_val = args[0].get_arg_value()
            if args[0].get_arg_type() != "label":
                exit(53)
            if arg0_val not in defined_labels:
                exit(52)
            
            order = defined_labels[arg0_val]
            place = find_inst_by_order(int(order))
            interpret(Instruction.listOfInstructions[place:])
            break

        elif i.get_name() == "JUMPIFEQ":
            check_arg_amount(3, i)
            arg0_val = args[0].get_arg_value()
            if args[0].get_arg_type() != "label":
                exit(53)
            if arg0_val not in defined_labels:
                exit(52)

            order = defined_labels[arg0_val]
            place = find_inst_by_order(int(order))

            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()

            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if typ1 == "nil" or typ2 == "nil":
                interpret(Instruction.listOfInstructions[place:])
                break
            elif typ1 == typ2:
                if arg1_val == arg2_val:
                    interpret(Instruction.listOfInstructions[place:])
                    break
            else:
                exit(53)

        elif i.get_name() == "JUMPIFNEQ":
            check_arg_amount(3, i)
            arg0_val = args[0].get_arg_value()
            if args[0].get_arg_type() != "label":
                exit(53)
            if arg0_val not in defined_labels:
                exit(52)

            order = defined_labels[arg0_val]
            place = find_inst_by_order(int(order))

            typ1 = args[1].get_arg_type()
            typ2 = args[2].get_arg_type()
            arg1_val = args[1].get_arg_value()
            arg2_val = args[2].get_arg_value()

            if typ1 == "var":
                if not check_var_existance(args[1], g_frame, l_frame, t_frame):
                    exit(54)
                arg1_val = check_var_value(args[1], g_frame, l_frame, t_frame)
                if arg1_val == None:
                    exit(56)
                typ1 = check_var_type(arg1_val)
            if typ2 == "var":
                if not check_var_existance(args[2], g_frame, l_frame, t_frame):
                    exit(54)
                arg2_val = check_var_value(args[2], g_frame, l_frame, t_frame)
                if arg2_val == None:
                    exit(56)
                typ2 = check_var_type(arg2_val)
            if typ1 == "nil" or typ2 == "nil":
                interpret(Instruction.listOfInstructions[place:])
                break
            elif typ1 == typ2:
                if arg1_val != arg2_val:
                    interpret(Instruction.listOfInstructions[place:])
                    break
            else:
                exit(53)

        elif i.get_name() == "EXIT":
            check_arg_amount(1, i)
            check_symb_type(args[0])
            typ0 = args[0].get_arg_type()
            arg0_val = args[0].get_arg_value()
            if typ0 == "var":
                if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                    exit(54)
                arg0_val = check_var_value(args[0], g_frame, l_frame, t_frame)
                if arg0_val == None:
                    exit(56)
                typ0 = check_var_type(arg0_val)
            if typ0 != "int":
                exit(53)
            if int(arg0_val) not in range(0,50):
                exit(57)
            exit(int(arg0_val))

        elif i.get_name() == "DPRINT":
            check_arg_amount(1, i)
            check_symb_type(args[0])
            typ0 = args[0].get_arg_type()
            arg0_val = args[0].get_arg_value()
            if typ0 == "var":
                if not check_var_existance(args[0], g_frame, l_frame, t_frame):
                    exit(54)
                arg0_val = check_var_value(args[0], g_frame, l_frame, t_frame)
                if arg0_val == None:
                    exit(56)
            sys.stderr.write(arg0_val)

        elif i.get_name() == "BREAK":
            check_arg_amount(0, i)
            sys.stderr.write("Global frame:", g_frame)
            sys.stderr.write("Local frame:", l_frame)
            sys.stderr.write("Temporary frame:", t_frame)
            sys.stderr.write("Stack:", stack)
            sys.stderr.write("Position:", i.get_order())

if __name__ == "__main__":   
    args = sys.argv

    if len(args) < 2 or len(args) > 3:
        exit(10)
    if len(args) == 2:
        if args[1] == "--help":
            print("Usage:")
            exit()
        else:
            splitted_first_arg = args[1].split("=")
            if len(splitted_first_arg) == 1:
                exit(10)
            if splitted_first_arg[0] == "--source":
                source_file = splitted_first_arg[1]
                input_file = sys.stdin
            elif splitted_first_arg[0] == "--input":
                input_file = splitted_first_arg[1]
                source_file = sys.stdin
            else:
                exit(10)

    elif len(args) == 3:
        splitted_first_arg = args[1].split("=")
        if len(splitted_first_arg) == 1:
            exit(10)
        if splitted_first_arg[0] == "--source":
            source_file = splitted_first_arg[1]
            splitted_second_arg = args[2].split("=")
            if len(splitted_second_arg) == 1:
                exit(10)
            if splitted_second_arg[0] == "--input":
                input_file = splitted_second_arg[1]
            else:
                exit(10)
        elif splitted_first_arg[0] == "--input":
            input_file = splitted_first_arg[1]
            splitted_second_arg = args[2].split("=")
            if len(splitted_second_arg) == 1:
                exit(10)
            if splitted_second_arg[0] == "--source":
                source_file = splitted_second_arg[1]
            else:
                exit(10)
        else:
            exit(10)

    try:
        tree = ET.parse(source_file)
        root = tree.getroot()
    except:
        exit(31)
    
    if root.tag != "program" or "language" not in root.attrib or root.attrib["language"] != "IPPcode23":
        exit(32)
    amount_of_instructions = 0
    for node in root:
        if node.tag != "instruction" or "opcode" not in node.attrib or "order" not in node.attrib:
            exit(32)
        if node.attrib["opcode"].upper() not in possible_opcodes:
            exit(32)
        arg_list = []
        for subnode in node:
            arg_list.append(subnode.tag)
            if not (re.match(r"arg[123]", subnode.tag)):
                exit(32)
            if subnode.tag == "arg2" and "arg1" not in arg_list:
                exit(32)
            if subnode.tag == "arg3" and "arg1" not in arg_list and "arg2" not in arg_list:
                exit(32)
            if "type" not in subnode.attrib or subnode.attrib["type"] not in possible_types:
                exit(32)

    
    for node in root:
        inst = Instruction(node.attrib["opcode"], node.attrib["order"])
        for subnode in node:
            if subnode.attrib["type"] == "var":
                splitted = subnode.text.split("@")
                inst.add_argument(subnode.attrib["type"], splitted[1], splitted[0])
            else:
                inst.add_argument(subnode.attrib["type"], subnode.text, None)
    mySort(Instruction.listOfInstructions)
    
    fisrt_walktrough(Instruction.listOfInstructions)
    
    interpret(Instruction.listOfInstructions)
    