<?php
/*
 * Reverse Polish Notation
 * 逆波兰表达式
 *
 * 将四则混合运算字符串转换成逆波兰序列，并计算出结果
 *  
 */

class RPN {
   public  $strExpression = '';						//待计算的字符串表达式
   public  $strRPN	  = '';						//生成的逆波兰表达式
   public  $nValue	  = 0;						//表达式计算的结果

   private $_expression   = Array();					//拆分为数组的计算表达式
   private $_rpnexp 	  = Array();					//队列，用于存放逆波兰表达式
   private $_stack 	  = Array('#');					//堆栈，用于存放操作符
   private $_priority 	  = Array('#' => 0, '(' =>1, '*' => 3, '/' => 3, '+' =>2, '-' => 2);   //计算优先级设定
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
      $bRightParentheses=false;					//记录“)”出现的状态
      $arrLeftParentheses=Array();				//左括号偏移，保存左括号前的最后一个+/-运算的位置（指向数组$this->_rpnexp）
      $nLeftParenthesesPointer=0;				//当前左括号指针
      $nCheckParentheses=0;					//用于检测左右括号是否对应
      $len = count($this->_expression);				//字符串表达式已经转成了数组
      for($i = 0; $i < $len; $i++)				//遍历数组
      {
/*
echo "\nLOOP: ".$i. "\t ORDER:". count($this->_rpnexp)."\t LPC:\t".$nLeftParenthesesPointer."\n";
echo "LP:\t";print_r($arrLeftParentheses);
echo "STACK:\t";print_r($this->_stack);
echo "RPN:\t";print_r($this->_rpnexp);
*/
         $str = $this->_expression[$i];
         if ($str == '(')						//括号优先级最高，先检测是否有左括号出现
         {
            $nCheckParentheses++;						//遇到左括号，加1；遇到右括号，减1
            $nLeftParenthesesPointer++;
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
            $nCheckParentheses--;						//遇到右括号，减1
            $bRightParentheses=true;						//右括号已出现，之后，需要检测并处理括号前有减法操作的情况，
										//在这种情况下，一旦出现新的减法操作，应该优先处理括号前的减法。
										//这是常规算法里所没有的。
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
            $this->_rpnexp[] = array_pop($this->_stack);			//这个操作，无论“+、-、*、/”，都要追加到结果数组中
            if($this->_priority[$str]==2)					//加减需要额外的操作，把括号对应的运算添加进来
            {
               $arrLeftParentheses[$nLeftParenthesesPointer]=$str;			//保存当前操作符到对应的括号所对应的偏移地址
               if($bRightParentheses )							//如果右括号已经出现，就表示当前是括号结束后的第一个低优先级的运算（加或减）
               {
                  $nLeftParenthesesPointer--;							//左括号计数器在这里减1
                  if( isset($arrLeftParentheses[$nLeftParenthesesPointer-1]))			//如果当前左括号前存在+/-操作，就需要立即执行
                  {
                     $this->_rpnexp[] = $arrLeftParentheses[$nLeftParenthesesPointer-1];		//将当前左括号前的最后一个+/-操作符放入结果数组中
                     array_pop($this->_stack);								//清一次堆栈顶部数据
                  }
                  $bRightParentheses=false;							//右括号标志位归位
               }
            }
            $this->_stack[] = $str;						//将当前运算符压入堆栈
            continue;								//立刻进入下一次循环
         } 
         else								//当前操作优先级高
         {
            if($this->_priority[$str]==2)
               $arrLeftParentheses[$nLeftParenthesesPointer]=$str;//count($this->_stack);	//保存左括号前的位置
            $this->_stack[] = $str;						//直接将操作符压入堆栈
            continue;								//直接进入下一次循环
         }
      }

      for($i = count($this->_stack); $i >= 0; $i--)			//倒序检测堆栈中是否有遗漏的操作
      {
         if (end($this->_stack) == '#') break;					//检测到搭底部，结束
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
         if(!in_array($this->_rpnexp[$i],$type))		//非计算符号，则认定为数字/变量
         {
            if(!is_numeric($this->_rpnexp[$i])) $bFormula=TRUE;
            array_unshift($data,intval($this->_rpnexp[$i]));	//将数据(数字/变量)插入到数组$data的开头
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
                  array_unshift($data,intval($val2/$val1));
                  break;
               //default:					//由于前面if里的in_array()已经过滤了非“+,-,*,/”的情况，
               //   break;					//所以这里的default可以安心地去掉。
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



//13插入7前，22插入16前
/*
调整条件：
1.括号前为减法；
2.括号结束后，下一个除了乘除的操作就是减法；

*/

/*
"-1-(2-3*(4-5))*3*5-4-4*4*4"	=>1 - 2 3 4 5 - * - 3 * 5 * - 4 - 4 4 * 4 * -
"-1-(2-3*(4-5))*3-4-4*4*4"	=>1 - 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * - 
"1-(2-3*(4-5))*3-4-4*4*4"	=>  1 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * -   
"1+(2-3*(4-5))*3-4-4*4*4" 	=>  1 2 3 4 5 - * - 3 * + 4 - 4 4 * 4 * -

*/

$expression ="1-2*4--4";//1 - 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * - //"1-(2-3*(4-5))*3-4-4*4*4"=>1 2 3 4 5 - * - 3 * - 4 - 4 4 * 4 * -   //"1+(2-3*(4-5))*3-4-4*4*4" =>1 2 3 4 5 - * - 3 * + 4 - 4 4 * 4 * -
echo $expression."=";
$mathrpn = new RPN();

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





/*End of php*/