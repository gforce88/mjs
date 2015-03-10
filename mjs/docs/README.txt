部署文档

服务器创建
1.服务器操作系统，centos.5.5
2.软件要求，安装php,安装mysql

部署步骤
1.拷贝msj目录下的所有文件到 php的 httpd/htdocs 下
2.修改 application.ini文件中的
	mail.port = 
	mail.host = 
	mail.username = 
	mail.password = 
	指定邮件发送账号
  修改
  ;us tropo
    tropo.url = http://api.tropo.com/1.0/sessions
  ;jp tropo
    ;tropo.url = https://tropo-gw01.unisrv.jp/sessions	
  开启日本的tropourl

3.在服务器上按照 shell/crontab中的内容，修改定时任务，
	一共有4个依次分别为，
		1.每分钟运行的电话任务，
		2.每分钟运行的提示电话任务，
		3.每周报表任务，
		4.每月重置学生账号时间任务
4.修改配置文件中的 app.hostip = "http://165.225.149.30" 地址改为服务器地址 指定语音文件

5.需要修改的js文件 session-create.js
6.需要修改的css文件
	module.admin.page.signup.min.css
	module.admin.page.tables.min.css
	module.admin.page.courses_2.min.css
	module.admin.page.form_elements.min.css
	module.admin.page.login.min.css