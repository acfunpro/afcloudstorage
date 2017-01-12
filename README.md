####安装方法
	
	github 地址
		https://github.com/acfunpro/afcloudstorage.git
		
	composer require acfunpro/afcloudstorage
	
	在/config/app.php文件中加入
		'afcloud' => array(
			'form'=>'admin',
			'take' => 10
		),
		form值用来与url中_afForm参数对比，如果相等则为后台请求,若不设置默认为admin
		take值为限制返回条数，默认为20条
		在providers中加入
			Jenssegers\Mongodb\MongodbServiceProvider::class,
			Mews\Purifier\PurifierServiceProvider::class,
		在aliases加入
			'Mongo'     => Jenssegers\Mongodb\MongodbServiceProvider::class,
			'Purifier' => Mews\Purifier\Facades\Purifier::class,
		
	在/app/Http/routes.php加入
	Route::group( [ 'prefix' => 'api', 'middleware' => 'AfCloud' ] , function(){
		Route::resource( "auto" , "AfCloudController" );
	});
	
	执行 $ php artisan make:middleware AfCloudMiddleware
	生成/app/Http/Middleware/目录下生成AfCloudMiddleware.php
	修改handle方法为 因为_afClass是必须存在的，加入中间件判断
	public function handle($request, Closure $next)
	{
		$arrInputData = Input::all();
		if( !empty( $arrInputData[ '_afClass' ] ) && ! preg_match( '/[^\w\-]/' , $arrInputData[ '_afClass' ] ) )
		{
			return $next($request);
		}
		else
		{
			return response('Unauthorized.', 401);
		}
	}
	在加入/app/Http/Kernel.php加入
		'AfCloud' => \App\Http\Middleware\AfCloudMiddleware::class,
	
	在/app/Http/Middleware/VerifyCsrfToken.php文件$except中加入
		'/api*'
	
	在文件中使用
		use acfunpro\afcloudstorage\AfCloudStorage;
	
	// 如果使用vdate
		在App\Http\Controllers.php 文件中加入
		use dekuan\vdata\CRemote;
		function __construct()
		{
			$this->m_sAcceptedVersion = CRemote::GetAcceptedVersionEx();
		}
		
	部署完毕后在根目录执行
			php artisan optimize


####说明
	采用mongodb存储数据
	保留表名：_SetupTables (对所有表的列设置信息)
			_LogTables   (接口请求日志)
	请求示例：
		{url}?_afClass=test&_afWhere={"name":["lk","test%"],"id":["gt":"3"]}&_afOther={"order":["sort","asc"]}
			
####提供方法
	基于RESTful设计原则
	
	$arrOutPutData 接收返回数据
	$sErroeMsg     返回错误信息
	$nCall         错误码 0为正常
	
	获取全部对象
		$AfCloud = AfCloudStorage::GetInstance();
		$nCall  = $AfCloud->GetIndex( $arrOutPutData, $sErroeMsg );

	获取一个对象
		$nCall  = $AfCloud->GetShow( $arrOutPutData, $sErroeMsg, $id );
		
	创建对象
		$nCall  = $AfCloud->PostStore( $arrOutPutData, $sErroeMsg );
	
	修改对象
		$nCall  = $AfCloud->PostStore( $arrOutPutData, $sErroeMsg, $id );
	
	删除对象
		$nCall  = $AfCloud->GetDestroy( $arrOutPutData, $sErroeMsg, $id );
	
	获取对应表的设置数据   $vFlag为false 返回所有数据 / true 只返回相关的字段
		$nCall  = $AfCloud->GetTablesColumn( $vFlag = false );
	
	设置参数
		参数： $arrMData 键名接受 _afDBTableName，_afArrInputData
		          _afDBTableName  操作的表名
		          _afArrInputData 条件设置 _afForm，_afWhere，_afOther 
		      $bFlag false将_afArrInputData的参数覆盖url的参数
		              true将_afArrInputData的参数追加到url的参数
		$nCall  = $AfCloud->SetVar( $arrMData = [], $bFlag = false );

	获取参数
			_afRequestForm  返回值 Index为前台请求 ／ Admin为后台请求
			_afDBTableName  返回值 操作的表名
			_afArrInputData 返回值 查询条件
		$nCall  = $AfCloud->GetVar();
		
	获取对_afid
		定义为主键id生成规则：substr(md5(microtime().rand(0,9999).rand(0,9999)),8,16);
		$nCall  = $AfCloud->GetAfid();
		
####前台请求URL
	get     {url}?_afClass=test   获取全部对象
			实现方法  GetIndex( array & $arrOutputData = [], & $sErroeMsg = '' )
			
	get     {url}/1?_afClass=test  获取一个对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法  GetShow( array & $arrOutputData = [], & $sErroeMsg = '', $id )
			
	post    {url}?_afClass=test   创建对象
			实现方法  PostStore( array & $arrOutputData = [], & $sErroeMsg = '')

	put     {url}/1?_afClass=test  修改一个对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法	PostStore( array & $arrOutputData = [], & $sErroeMsg = '', $id = '' )

	delete  {url}/1?_afClass=test  删除对象 默认字段为id
				若字段名为mid则写成
				{url}/mid.1
			实现方法	GetDestroy( &$arrOutputData, &$sErroeMsg, $id = '' )

####前台参数
	带*为必填
		*_afClass = test       // 表名
		 _afForm  = admin      // 标明来源
		
		_afWhere = {"name":"abc"}   // 返回name值为abc的数据
		_afWhere = {"id",["gt","5"]} // 返回id大于5的数据
			// gt处可选参数
			[
				'gt' => '>' ,
				'ge' => '>=',
				'lt' => '<' ,
				'le' => '<=',
				'eq' => '=' ,
				'ne' => '!=',
				'lk' => 'like'     // {"name",["lk","%name%"]}
			];
		_afWhere = {"id":["in",["1","2"]]}      // 返回id in(1,2)
		_afWhere = {"id":["nin",["1","2"]]}      // 返回id notin(1,2)
		_afWhere = {"sort":["bw",["1","10"]]}   // 返回sort为 1到10 之间的数据
		_afWhere = {"sort":["nbw",["1","10"]]}   // 返回sort为 1到10 之外的数据
		
		
		   // 返回0到10条数据
		_afOther = {"limit":"10"}
				{"limit":["0","10"]}
		   // 按照sort排序
		_afOther = {"order","sort"}       // 倒序
				{"order",["sort","asc"]}   // 正序
		   // groupby sid
		_afOther = {"group":"sid"}
		   // 获取排序后的数量
		_afOther = {"group":"sid","num":"1"}
			// id为2的数据total字段递增
		_afWhere={"id":"2"}&_afOther={"inc","total"}        // 递增 1
		_afWhere={"id":"2"}&_afOther={"inc",["total","5"]}    // 递增 5
			// id为2的数据total字段递减
		_afWhere={"id":"2"}&_afOther={"dec","total"}        // 递减 1
		_afWhere={"id":"2"}&_afOther={"dec",["total","5"]}    // 递减 5
		_afWhere={"num":["eq","0"],"_or":{"start":["gt","2"],"end":"2"}} // num等于0 或者 start大于2 或者 end 等于 2	
	
		
		
		// item = once 返回单条; 默认返回所有; (num, max, min, avg, sum 参数除外)
		_afGet   = {"num":"1"}	// 返回符合条件的总数据量
		_afGet   = {"max":"id"} // 返回最大id  可选参数有( max, min, avg, sum ）



####控制器中调用
        
        // 实例化
        $AfCloud = AfCloudStorage::GetInstance();
        
        // 调用SetVar方法设置操作的表
              (_SetupTables 表所有操作一定要加_afForm={default_admin})
              _afArrInputData 参数：
              			_afForm   设置请求来源
              			_afWhere  请求条件
              			_afOther  排序等操作
              			_afGet    聚合函数
              	_arrSelectAllow 设置可查询字段
              	                _afWhere 对应字段名
              	                _afOthrt 可以使用limit／group等操作
              	                _afGet   可以查询聚合函数和count
              	                不可操作设置为 []
              	                
              _afTake 设置查询条数
              			以上参数均参考 @前台参数 内容
        $AfCloud -> SetVar(
            [
                '_afDBTableName'=>'_SetupTables',
                '_afArrInputData'=>[
                                    '_afForm'  => 'admin',
                                    '_afWhere' => '{"_Table":"ouqi"}',
                                    '_afGet'   => '{"num":"1"}'
                                ], 	              '_arrSelectAllow' => [                                   '_afWhere' => ['status', 'type'],                                   '_afOther' => [],
                                  '_afGet'   => ['num']                                 ]
            ]
        );
        
        $AfCloud->setVar($sArrMData, true);
        如果setVar的第二个参数设置为 true 第一个参数内容会覆盖用户输入
        
        // 获取符合条件的所有对象
        $AfCloud->GetIndex( array & $arrOutputData = [], & $sErroeMsg = '' );
			
			同 @前台请求方式 


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
        _Tag        // 对应表单标签
   
   	// 配置表字段类型
        _Table      =>    str  // 表名
        _Column     =>    str  // 列名
        _Type       =>    str  // 类型
        _Default    =>    str  // 默认值
        _Describe   =>    str  // 描述
        _Verify     =>    str  // 验证规则
        _Sort       =>    int  // 排序
        _Display    =>    int  // 客户端不可见
        _Tag        =>    str  // 对应表单标签
    
   	// 配置表字段验证规则
			使用validator（laravel框架内自带验证）
		
        _Table      =>    ['required'] // 表名
        _Column     =>    ['required'] // 列名
        _Type       =>    ['required'] // 类型
        _Default    =>    []         // 默认值
        _Describe   =>    ['required'] // 描述
        _Verify     =>    []         // 验证规则
        _Sort       =>    ['number']   // 排序
        _Display    =>    ['number']   // 客户端不可见
        _Tag        =>    []           // 
    
   	// 配置表字段描述
        _Table      =>    表名        // 表名
        _Column     =>    列名        // 列名
        _Type       =>    类型        // 类型
        _Default    =>    默认值      // 默认值
        _Describe   =>    描述        // 描述
        _Verify     =>    验证规则     // 验证规则
        _Sort       =>    排序        // 排序
        _Display    =>    客户端不可见 // 客户端不可见
        _Tag        =>    标签        // 标签
    
####注：
    
			'unique'修改时默认强制忽略当前id
			{{url}}/api/auto/ba786617bcebb3d5
			如果使用自定义字段可写为
			{{url}}/api/auto/id.123 将强制忽略id为123的数据
			
			
			默认不能访问_SetupTables表中内容，需要在url中添加&form={default_admin}，确认是后台请求




