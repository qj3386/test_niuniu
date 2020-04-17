function count_js () { document.writeln(''); }

$().ready(function(){
	$("#go_top").css("display", "none");
	$(window).scroll(function () {
        if ($(window).scrollTop() > 100) {
            $("#go_top").fadeIn(500);
        } else {
            $("#go_top").fadeOut(500);
        }
    });
    //当点击跳转链接后，回到页面顶部位置
    $("#go_top").click(function () {
        $('body,html').animate({scrollTop: 0}, 300);
        return false;
    });
	
});

//解决IOS页面返回不刷新的问题
window.onpageshow = function (event) {
	//event.persisted判断是否后退进入
	if (event.persisted || window.performance && window.performance.navigation.type == 2) {
		var u = navigator.userAgent;
		var is_android = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
		var is_ios = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
		if (is_ios) {
			window.location.reload();
		}
	}
};

//解决APP频繁唤起Safari浏览器
if(("standalone" in window.navigator) && window.navigator.standalone){
	var noddy, remotes = false;
	document.addEventListener('click', function(event) {
		noddy = event.target;
		while(noddy.nodeName !== "A" && noddy.nodeName !== "HTML") {
			noddy = noddy.parentNode;
		}
		if('href' in noddy && noddy.href.indexOf('http') !== -1 && (noddy.href.indexOf(document.location.host) !== -1 || remotes)){
			event.preventDefault();
			document.location.href = noddy.href;
		}
	},false);
}

