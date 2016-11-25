####安装方法
	composer require acfunpro/afcloudstorage 
	
	github 地址
		https://github.com/acfunpro/afcloudstorage.git
		
	在/config/app.php文件中加入
		'afcloudform' => 'default_admin'
	afcloudform值用来与url中form参数对比，如果相等则为后台请求
	若不设置默认为admin


####说明
	采用mongodb存储数据
	保留表名：_SetupTables (对所有表的列设置信息)
			_LogTables   (接口请求日志)
	请求示例：
		{url}?class=test&where={"name":["lk","test%"],"id":["gt":"3"]}&other={"order":["sort","asc"]}&get={"item":"all"}
			
####请求方式
	基于RESTful设计原则

####请求URL
	get     {url}/   获取全部对象
			实现方法  GetIndex( array & $arrOutputData = [], & $sErroeMsg = '' )
			
	get     {url}//1  获取一个对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法  GetShow( array & $arrOutputData = [], & $sErroeMsg = '', $id )
			
	post    {url}/   创建对象
			实现方法  PostStore( array & $arrOutputData = [], & $sErroeMsg = '')

	put     {url}/1  修改一个对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法	PostStore( array & $arrOutputData = [], & $sErroeMsg = '', $id = '' )

	delete  {url}/1  删除对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法	GetDestroy( &$arrOutputData, &$sErroeMsg, $id = '' )

####参数
	带*为必填
		*class = test       // 表名
		 form  = admin      // 标明来源
		
		where = {"name":"abc"}   // 返回name值为abc的数据
		where = {"id",["gt","5"]} // 返回id大于5的数据
			// gt处可选参数
			[
				'gt' => '>' ,
				'ge' => '>=',
				'lt' => '<' ,
				'le' => '<=',
				'et' => '=' ,
				'nt' => '!=',
				'lk' => 'like'     // {"name",["lk","%name%"]}
			];
		where = {"id":["in",["1","2"]]}      // 返回id in(1,2)
		where = {"sort":["bw",["1","10"]]}   // 返回sort为 1到10 之间的数据
		
		
		   // 返回0到10条数据
		other = {"limit":"10"}&get={"get":"all"}
				{"limit":["0","10"]}&get={"get":"all"}
		   // 按照sort排序
		other = {"order",["sort"]}&get={"get":"all"}      // 倒序
				{"order",["sort","asc"]}&get={"get":"all"}   // 正序
		   // groupby sid
		other = {"group":"sid"}&get={"get":"all"}
			// id为2的数据total字段递增
		where={"id":"2"}&other={"inc",["total"]}        // 递增 1
		where={"id":"2"}&other={"inc",["total","5"]}    // 递增 5
			// id为2的数据total字段递减
		where={"id":"2"}&other={"dec",["total"]}        // 递减 1
		where={"id":"2"}&other={"dec",["total","5"]}    // 递减 5
		
		
		get   = {"item":"once"}  
		// item = once 返回单条; item = all 返回所有; 不设置则不返回数据(num, max, min, avg, sum 参数除外)
		get   = {"num":"1"}	// 返回符合条件的总数据量
		get   = {"max":"id"} // 返回最大id  可选参数有( max, min, avg, sum ）


####配置表信息设置
    _LogTables        // 日志表
    _SetupTables      // 配置表信息
    
		// 配置表字段
        _Table      // 表名
        _Column     // 列名
        _Type       // 类型
        _Default    // 默认值
        _Describe   // 描述
        _Verify     // 验证规则
        _Sort       // 排序
        _Display    // 值为1客户端不可见
   
   	// 配置表字段类型
        _Table      =>    str  // 表名
        _Column     =>    str  // 列名
        _Type       =>    str  // 类型
        _Default    =>    str  // 默认值
        _Describe   =>    str  // 描述
        _Verify     =>    str  // 验证规则
        _Sort       =>    int  // 排序
        _Display    =>    int  // 客户端不可见
    
   	// 配置表字段验证规则
        _Table      =>    required // 表名
        _Column     =>    required // 列名
        _Type       =>    required // 类型
        _Default    =>    ''       // 默认值
        _Describe   =>    required // 描述
        _Verify     =>    ''       // 验证规则
        _Sort       =>    number   // 排序
        _Display    =>    number   // 客户端不可见
    
   	// 配置表字段描述
        _Table      =>    表名        // 表名
        _Column     =>    列名        // 列名
        _Type       =>    类型        // 类型
        _Default    =>    默认值      // 默认值
        _Describe   =>    描述        // 描述
        _Verify     =>    验证规则     // 验证规则
        _Sort       =>    排序        // 排序
        _Display    =>    客户端不可见 // 客户端不可见
    

 


