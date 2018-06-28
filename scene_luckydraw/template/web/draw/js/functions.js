// The Central Randomizer 1.3 (C) 1997 by Paul Houle (houle@msc.cornell.edu)
// See: http://www.msc.cornell.edu/~houle/javascript/randomizer.html 
rnd.today=new Date();
rnd.seed=rnd.today.getTime();
function rnd() {
　　　　rnd.seed = (rnd.seed*9301+49297) % 233280;
　　　　return rnd.seed/(233280.0);
};
function rand(number) {
　　　　return Math.ceil(rnd()*number);
};
// end central randomizer. -->

/**
 * JS判断一个值是否存在数组中
 */
 
/*// 定义一个判断函数
var in_array = function(arr){
  // 判断参数是不是数组
  var isArr = arr && console.log(
      typeof arr==='object' ? arr.constructor===Array ? arr.length ? arr.length===1 ? arr[0]:arr.join(','):'an empty array': arr.constructor: typeof arr 
    );
 
  // 不是数组则抛出异常
  if(!isArr){
    throw "arguments is not Array"; 
  }
 
  // 遍历是否在数组中
  for(var i=0,k=arr.length;i<k;i++){
    if(this==arr[i]){
      return true;  
    }
  }
 
  // 如果不在数组中就会返回false
  return false;
}
// 给字符串添加原型
String.prototype.in_array = in_array;
// 给数字类型添加原型
Number.prototype.in_array = in_array;

Array.prototype.indexOf = function(val) {
for (var i = 0; i < this.length; i++) {
if (this[i] == val) return i;
}
return -1;
};

Array.prototype.remove = function(val) {
var index = this.indexOf(val);
if (index > -1) {
this.splice(index, 1);
}
};*/

function preloadimages(arr){   
    var newimages=[], loadedimages=0
    var postaction=function(){}  //此处增加了一个postaction函数
    var arr=(typeof arr!="object")? [arr] : arr
    function imageloadpost(){
        loadedimages++
        if (loadedimages==arr.length){
            postaction(newimages) //加载完成用我们调用postaction函数并将newimages数组做为参数传递进去
        }
    }
    for (var i=0; i<arr.length; i++){
        if ((arr[i]!=undefined) && (arr[i].length > 0)) {
            newimages[i] = new Image()
            newimages[i].src = arr[i]
            newimages[i].onload = function () {
                imageloadpost()
            }
            newimages[i].onerror = function () {
                imageloadpost()
            }
        }
    }
    return { //此处返回一个空白对象的done方法
        done:function(f){
            postaction=f || postaction
        }
    }
}