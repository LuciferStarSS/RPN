<?php
/*
 * Reverse Polish Notation
 * 逆波兰表达式
 *
 * 将四则混合运算字符串转换成逆波兰序列，并计算出结果
 * 之前想复杂了，其实就是一个减法的处理问题。
 *  
已测样例如下：

$test=Array(
//字符串				逆波兰表达式						计算结果

"10-2-3*4+4",				//10 2 - 3 4 * - 4 +					//0
"10-2-3*(4+5)-4-3",			//10 2 - 3 4 5 + * - 4 - 3 -				//-26
//遇到减法，一定要先处理减法，跟括号无关

"1+5-6-(7+(1+2))*2+5",			//1 5 + 6 - 7 1 2 + + 2 * - 5 -				//-15

"1+4/2-3*4-5-6-(7+8*3-2*(1+2)/3)*2-5+6",
//1 4 2 / 3 4 * - 5 - 6 - 7 8 3 * 2 1 2 + * 3 / - + 2 * - 5 - 6 + +
//-77

"1+2+3+4+5+6",				//1 2 + 3 + 4 + 5 + 6 + 				//21
"1+2+(3+4)+5+6",			//1 2 + 3 4 + + + 5 + 6 +				//21
"1+(2+3)+(4+5)+6",			//1 2 3 + + + 4 5 + + + 6 +				//21
"1+2*3+4*5+6",				//1 2 3 * 4 5 * 6 + + +					//33
"1+2+(3*4)+5+6",			//1 2 + 3 4 * + + 5 + 6 +				//26
"1+2*3/4*5+6",				//1 2 3 * 4 / 5 * 6 + +					//14.5
"1+2+(3*4)/5+6",			//1 2 + 3 4 * 5 / + 6 +					//11.4

"1-2+(3*4)/5-6",			//1 2 - 3 4 * 5 / + 6 -					//-4.6
"1-2-(3*4)/5-6",			//1 2 - 3 4 * 5 / - 6 -					//-9.4
"1-(3*4)/5-6",				//1 3 4 * 5 / - 6 -					//-7.4

"1-4*4*4-2",				//1 4 4 * 4 * - 2 -					//-65
"1-(2-3*(4-5)-3)*3-4-4*4*4",		//1 2 3 4 5 - * - 3 - 3 * - 4 - 4 4 * 4 * -		//-73
"1-(2-(4-5))*3*4-4",			//1 2 4 5 - - 3 * 4 * - 4 -				//-39


"1-(2-(3*4)/2-3-4+5)/2-1+3-4",		//1 2 3 4 * 2 / - 3 - 4 - 5 + 2 / - 1 - 3 + 4 -		//2
"1-(2-(3*4)/5-3-4+5)/7-1+3-4",		//1 2 3 4 * 5 / - 3 - 4 - 5 + 7 / - 1 - 3 + 4 -		//-0.65714285714286
"1-(2-(3*4)/5-(3-4)*3+5)/7-1+3-4",	//1 2 3 4 * 5 / - 3 4 - 3 * - 5 + 7 / - 1 - 3 + 4 -	//-2.0857142857143

"123+543/3-34*23-43-21-(15+28*43-2*(12+32)/2)*2-51+60",
//123 543 3 / 34 23 * - 43 - 21 - 15 28 43 * 2 12 32 + * 2 / - + 2 * - 51 - 60 + +		
//-2883

"123+543/3/2-34*23*3-43-21-(15+28*43-2*4*(12+32)/2)*2*5-51+60",
//123 543 3 / 2 / 34 23 * 3 * - 43 - 21 - 15 28 43 * 2 4 * 12 32 + * 2 / - + 2 * 5 * - 51 - 60 + +	
//-12617.5

"123+543/3/2-34*23*3-43/21-(15+8*43-2*4*(12+32)/2)*2*5-51*2-6",
//123 543 3 / 2 / 34 23 * 3 * - 43 21 / - 15 8 43 * 2 4 * 12 32 + * 2 / - + 2 * 5 * - 51 2 * - 6 - +	
//-4072.5476190476

);
 *  
 *
 *  
 */
define( 'DEBUG', FALSE);						//调试用

class RPN {
   public  $strExpression = '';						//待计算的字符串表达式
   public  $strRPN	  = '';						//生成的逆波兰表达式
   public  $nValue	  = 0;						//表达式计算的结果

   private $_expression   = Array();					//拆分为数组的计算表达式
   private $_rpnexp 	  = Array();					//队列，用于存放逆波兰表达式
   private $_stack 	  = Array('#');					//堆栈，用于存放操作符
   private $_priority 	  = Array('#' => 0, '(' =>1, '*' => 3,    	//计算优先级设定
				  '/' => 3, '+' =>2, '-' => 2);
   private $_operator 	  = Array('(', '+', '-', '*', '/', ')');	//四则混合运算中的操作符

   //类初始化
   public function __construct() 
   {

   }

   //0.初始化逆波兰表达式数组
   //1.将四则混合运算字符串拆分为数组（若按原算法，拆分后的操作数只能是个位数。）
   //2.将数组按逆波兰排列
   //3.计算结果
   public function eval($strExpression)
   {
      $this->_rpnexp = Array();					//初始化。此类允许通过eval多次计算不同的表达式，所以初始化就放在这里了。
      $this->_stack = Array('#');
      $this->strExpression = $strExpression;			//保存传入的字符串
      $this->_expression = preg_split("/(\+)|(\-)|(\*)|(\/)|(\()|(\))/",$strExpression,-1,PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);//将字符串表达式转成数组
      //$this->exp2RPN();					//转换为逆波兰表达式
      return $this->evalRPN( $this->exp2RPN());			//计算逆波兰表达式，并返回计算结果
   }

   //对已拆分数组进行逆波兰处理
   private function exp2RPN() 
   {
      $nCheckParentheses=0;					//用于检测左右括号是否对应
      $len = count($this->_expression);				//字符串表达式已经转成了数组
      for($i = 0; $i < $len; $i++)				//遍历数组
      {
         if(DEBUG)
         {
            echo "\nLOOP: ".$i. "\t ORDER:". count($this->_rpnexp)."\n";
            echo "\n\n++++++++++++STACK+++++++++++:\t";print_r($this->_stack);
            echo "\n\nRPN:\t";print_r($this->_rpnexp);
         }

         $str = $this->_expression[$i];
         if ($str == '(')						//括号优先级最高，先检测是否有左括号出现
         {
            $nCheckParentheses++;						//遇到左括号，加1；遇到右括号，减1
            $this->_stack[] = $str;						//将左括号压入运算符号堆栈
            continue;								//立刻进入下一次循环
         } 
         else if ( !in_array($str, $this->_operator)) 			//非已定义的运算符号，即为操作数/变量
         {
            $this->_rpnexp[] = $str;						//放入输出结果数组中
            continue;								//立刻进入下一次循环
         }
         else if ($str == ')')						//右括号出现，表示有一个完整的括号结束了
         {
            $nCheckParentheses--;						//遇到左括号，加1；遇到右括号，减1
            for($j = count($this->_stack); $j >= 0; $j--)			//倒序检测运算符堆栈，把这一对括号中的操作都输出
            {
               $tmp = array_pop($this->_stack);						//取出堆栈顶的数据
               if ($tmp == "(") break;							//直到处理完当前的整个括号内数据
               else $this->_rpnexp[] = $tmp;						//需要将该数据放入输出结果数组中
            }
            continue;								//立刻进入下一次循环
         }
         else if (isset($this->_priority[end($this->_stack)]) && $this->_priority[$str] <= $this->_priority[end($this->_stack)]) //非括号内，非操作数，即为“+、-、*、/”四个操作，需要判断优先级
         {								  	//当前操作优先级比堆栈中最后一个的操作低，则需要处理减法问题
            echo DEBUG?"优先级变化:当前操作：$str\t上一个操作：".end($this->_stack)."\n":'';

            $this->_rpnexp[] = array_pop($this->_stack);			//这个操作，无论“+、-、*、/”，都要追加到结果数组中

            if(count($this->_stack)>1 && ($str=='-'|| $str=='+'))		//($str=='-'|| $str=='+') 即  $this->_priority[$str]==2
            {									//当$_stack中已经寄存有未处理的操作符，而当前又出现权限较低的+和-时
               $val1=array_pop($this->_stack);					//获取$_stack堆栈顶部数据
               if($val1=='-')							//当堆栈中积存的是“-”，则需要先算这个减法
               {
                  $this->_rpnexp[]=$val1;
               }
               else
                 array_push($this->_stack,$val1);				//否则把该操作符放回去
            }

            $this->_stack[] = $str;						//将当前运算符压入堆栈
            continue;								//立刻进入下一次循环
         } 
         else								//当前操作优先级高
         {
            $this->_stack[] = $str;						//直接将操作符压入堆栈
            continue;								//直接进入下一次循环
         }
      }

      for($i = count($this->_stack); $i >= 0; $i--)			//检测堆栈中是否有遗漏的操作
      {
         if (end($this->_stack) == '#') break;					//检测到达底部，结束
         $this->_rpnexp[] = array_pop($this->_stack);				//直接追加到结果数组
      }

      return ( $nCheckParentheses!=0 )?  FALSE:$this->_rpnexp;		//如果输入数据有误（比如括号不匹配，连续多个运算符叠加的情况暂时没有处理），就返回FALSE；否则返回包含逆波兰表达式数据的数组
   }

   //获取表达式的计算结果
   public function evalRPN($bSignal) 
   {
      if($bSignal===FALSE)
         return "括号匹配有问题。";

      $bFormula	= FALSE;
      $data	= Array();						//用于保存运算所需要的数/变量
      $type	= Array('+','-','*','/');				//限定了只能处理这四种运算

      for($i=0;$i<count($this->_rpnexp);$i++)
      {
         if(  $this->_rpnexp[$i]!=NULL)//数据莫名其妙地多了一个NULL，暂时屏蔽。
         {
            if(!in_array($this->_rpnexp[$i],$type))		//非计算符号，则认定为数字/变量
            {
               if(!is_numeric($this->_rpnexp[$i])) $bFormula=TRUE;
               array_unshift($data,$this->_rpnexp[$i]);	//将数据(数字/变量)插入到数组$data的开头
            }
            else							//处理“+,-,*,/”
            {
               $val1=array_shift($data);				//获取数组$data的第一个数据，并删除
               $val2=array_shift($data);				//获取数组$data的第一个数据，并删除
               switch($this->_rpnexp[$i])
               {
                  case '+':
                     array_unshift($data,$val2+$val1);		//将计算结果保存到数组$data的开头
                     break;
                  case '-':
                     array_unshift($data,$val2-$val1);
                     break;
                  case '*':
                     array_unshift($data,$val2*$val1);
                     break;
                  case '/':
                     array_unshift($data,$val2/$val1);
                     break;
                  //default:					//由于前面if里的in_array()已经过滤了非“+,-,*,/”的情况，
                  //   break;					//所以这里的default可以安心地去掉。
               }
            }
         }
      }
      return $this->nValue=$bFormula?FALSE:current($data);	//当输入里有无法计算的字母时，返回FALSE，否则返回计算后得到的数值。
   }

   //获取RPN数据，以数组的形式呈现
   public function getArrRPN()
   {
      return $this->_rpnexp;
   }

   //获取RPN数据，以字符串的形式呈现
   public function getStrRPN()
   {
      return implode(" ",$this->_rpnexp);
   }

   //获取表达式的计算结果
   public function getValueRPN()
   {
      return $this->nValue;
   }
};
//RPN类定义结束


//测试实例

/*
调整条件：
1.括号前为减法；
2.括号结束后，下一个除了乘除的操作就是减法；

*/


$test=Array(
//字符串				逆波兰表达式						计算结果

"10-2-3*4+4",				//10 2 - 3 4 * - 4 +					//0
"10-2-3*(4+5)-4-3",			//10 2 - 3 4 5 + * - 4 - 3 -				//-26
//遇到减法，一定要先处理减法，跟括号无关

"1+5-6-(7+(1+2))*2+5",			//1 5 + 6 - 7 1 2 + + 2 * - 5 -				//-15

"1+4/2-3*4-5-6-(7+8*3-2*(1+2)/3)*2-5+6",
//1 4 2 / 3 4 * - 5 - 6 - 7 8 3 * 2 1 2 + * 3 / - + 2 * - 5 - 6 + +
//-77

"1+2+3+4+5+6",				//1 2 + 3 + 4 + 5 + 6 + 				//21
"1+2+(3+4)+5+6",			//1 2 + 3 4 + + + 5 + 6 +				//21
"1+(2+3)+(4+5)+6",			//1 2 3 + + + 4 5 + + + 6 +				//21
"1+2*3+4*5+6",				//1 2 3 * 4 5 * 6 + + +					//33
"1+2+(3*4)+5+6",			//1 2 + 3 4 * + + 5 + 6 +				//26
"1+2*3/4*5+6",				//1 2 3 * 4 / 5 * 6 + +					//14.5
"1+2+(3*4)/5+6",			//1 2 + 3 4 * 5 / + 6 +					//11.4

"1-2+(3*4)/5-6",			//1 2 - 3 4 * 5 / + 6 -					//-4.6
"1-2-(3*4)/5-6",			//1 2 - 3 4 * 5 / - 6 -					//-9.4
"1-(3*4)/5-6",				//1 3 4 * 5 / - 6 -					//-7.4

"1-4*4*4-2",				//1 4 4 * 4 * - 2 -					//-65
"1-(2-3*(4-5)-3)*3-4-4*4*4",		//1 2 3 4 5 - * - 3 - 3 * - 4 - 4 4 * 4 * -		//-73
"1-(2-(4-5))*3*4-4",			//1 2 4 5 - - 3 * 4 * - 4 -				//-39


"1-(2-(3*4)/2-3-4+5)/2-1+3-4",		//1 2 3 4 * 2 / - 3 - 4 - 5 + 2 / - 1 - 3 + 4 -		//2
"1-(2-(3*4)/5-3-4+5)/7-1+3-4",		//1 2 3 4 * 5 / - 3 - 4 - 5 + 7 / - 1 - 3 + 4 -		//-0.65714285714286
"1-(2-(3*4)/5-(3-4)*3+5)/7-1+3-4",	//1 2 3 4 * 5 / - 3 4 - 3 * - 5 + 7 / - 1 - 3 + 4 -	//-2.0857142857143

"123+543/3-34*23-43-21-(15+28*43-2*(12+32)/2)*2-51+60",
//123 543 3 / 34 23 * - 43 - 21 - 15 28 43 * 2 12 32 + * 2 / - + 2 * - 51 - 60 + +		
//-2883

"123+543/3/2-34*23*3-43-21-(15+28*43-2*4*(12+32)/2)*2*5-51+60",
//123 543 3 / 2 / 34 23 * 3 * - 43 - 21 - 15 28 43 * 2 4 * 12 32 + * 2 / - + 2 * 5 * - 51 - 60 + +	
//-12617.5

"123+543/3/2-34*23*3-43/21-(15+8*43-2*4*(12+32)/2)*2*5-51*2-6",
//123 543 3 / 2 / 34 23 * 3 * - 43 21 / - 15 8 43 * 2 4 * 12 32 + * 2 / - + 2 * 5 * - 51 2 * - 6 - +	
//-4072.5476190476

);

$mathrpn = new RPN();

for($i=0;$i<count($test);$i++)
{
   echo "<font color=red>".$test[$i]."=</font>";
   echo "<pre>";
   $result=$mathrpn->eval($test[$i]);
   //var_dump($result);
   if($result!==FALSE) echo $result."\n";
   else echo "输入数据中含有不可计算的变量。请通过调用getStrRPN()或getArrRPN()查看。\n";

   print_r( $mathrpn->getStrRPN() );
   print_r( $mathrpn->getArrRPN() );
   print_r( $mathrpn->getValueRPN());
   echo "\n\n\n";
echo "</pre>";
}

exit;

$expression ="1-2*4--4";//1 - 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * - //"1-(2-3*(4-5))*3-4-4*4*4"=>1 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * -   //"1+(2-3*(4-5))*3-4-4*4*4" =>1 2 3 4 5 - * - 3 * + 4 - 4 4 * 4 * -
echo $expression."=";


$result=$mathrpn->eval($expression);
//echo $result;
if($result!==FALSE) echo $result;
else echo "输入数据中含有不可计算的变量。请通过调用getStrRPN()或getArrRPN()查看。";
echo "\n";
print_r( $mathrpn->getStrRPN() );
print_r( $mathrpn->getArrRPN() );
print_r( $mathrpn->getValueRPN());
echo "\n";

exit;

$expression ="1-(2-3*(4-5))))*3-4-4*4*4";//1 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * -   //"1+(2-3*(4-5))*3-4-4*4*4" =>1 2 3 4 5 - * - 3 * + 4 - 4 4 * 4 * -
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);
echo "\n";
print_r( $mathrpn->getStrRPN() );
print_r( $mathrpn->getArrRPN() );
print_r( $mathrpn->getValueRPN());
echo "\n";

exit;

$expression ="1-(2-(4-5))*3*4-4";//1 2 4 5 - - 3 * 4 * - 4 -
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);
echo "\n";
print_r( $mathrpn->getStrRPN() );
print_r( $mathrpn->getArrRPN() );
print_r( $mathrpn->getValueRPN());
echo "\n";

exit;



$expression ="1-(2-3*(4+5))*3+4";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
$result=$mathrpn->eval($expression);
if($result!==FALSE) echo $result;
else echo "表达式有错误。";
echo "\n";
print_r( $mathrpn->getStrRPN() );
print_r( $mathrpn->getArrRPN() );
print_r( $mathrpn->getValueRPN());
echo "\n";
exit;



$expression ="1-(2-(3*4)/2-3-4+5)/2-1+3-4";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);
echo "\n";
print_r( $mathrpn->getRPN($expression) );
echo "\n";
exit;

$expression ="1-(2-2*(3*4)/2*5-3-4+5)/2-1+3-4";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);

print_r( $mathrpn->getRPN($expression) );
echo "\n";
exit;


$expression ="1-2-(3*4)/5-6";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);

print_r( $mathrpn->getRPN($expression) );
echo "\n";
exit;



$expression ="1-2-3*4+12";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);
echo "\n";
print_r( $mathrpn->getRPN($expression) );


$expression ="1-(2+(3*4)-3)/2-1";// "(A*(B+C)-E+F)*G";//"1-3-(2*2)*5-3";//;//
echo $expression."=";
$mathrpn = new RPN();
echo $mathrpn->eval($expression);
echo "\n";
print_r( $mathrpn->getRPN($expression) );

$expression ="(A*(B+C)-E+F)*G";
echo $expression."=";
echo $mathrpn->eval($expression);
echo "\n";

print_r( $mathrpn->getRPN($expression) );
//print_r($exp);

//echo $mathrpn->evalRPN();


?>
