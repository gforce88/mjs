部署文档

服务器创建

1.软件要求，安装php，安装mysql ，安装svn
2.修改mysql的rewrtie ,
   将/opt/local/etc/httpd/httpd.conf 文件中的 AllowOverride None 修改为 AllowOverride All

部署步骤
1.执行SVN命令
	svn checkout https://incognito.sourcerepo.com/incognito/anonymous_call/mjs /opt/local/share/httpd/htdocs/mjs

2.在服务器上修改定时任务，
		crontab -l for display
		crontab -e for edit
		
		每分钟执行 运行的电话任务，
		* * * * * curl http://localhost/mjs/public/timer >> croncall.log
		每分钟执行 运行的提示电话任务，
		* * * * * curl http://localhost/mjs/public/timer/remind >> cronremind.log
		每周一 00：01：00执行  每周报表任务，
		1 0 * * 1 curl http://localhost/mjs/public/report >> cronremind.log
		
		每月1号  00：01：00 执行   每月重置学生账号时间任务
		1 0 1 * * curl http://localhost/mjs/public/report/reset >> cronremind.log
		
3.导入数据库文件 doc/Dump20150301.sql