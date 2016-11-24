<?php

namespace acfunpro\afcloudstorage;
use dekuan\delib\CLib;
use Request;
use Input;
use DB;


/**
 *  Client of cloud storage
 */
class AfCloudStorage
{
    protected static $g_cStaticInstance;

    protected $m_oDBLink;

    protected $m_sRequestType;

    protected $m_arrOutputData;

    public $m_sDBDriver;

    public $m_sDBTableName;

    public $m_arrInputData;

    public $m_sRequestForm;

    public function __construct()
    {
        date_default_timezone_set('PRC');
        // 数据过滤
        $this->m_arrInputData = clean( Input::all() , array('Attr.EnableID' => true) );

        // 验证有效的Key(下标)
        $this->_IsArrKey($this->m_arrInputData);

        // 判断是或否为后台请求
        if( ! empty( $this->m_arrInputData['form'] ) && $this->m_arrInputData['form'] == 'admin' )
        {
            $this->m_sRequestForm = 'admin';
        }

        // 获取操作表名
        $this->m_sDBTableName = isset( $this->m_arrInputData['class'] )?$this->m_arrInputData['class']:'';

        // 判断连接数据库(目前未做mysql数据库支持)
        if( 'mysql' == $this->m_sDBDriver )
        {
            $this->m_oDBLink = DB::table( $this->m_sDBTableName );
        }
        else
        {
            $this->m_oDBLink = DB::collection( $this->m_sDBTableName );
        }
    }

    // 单例
    static function GetInstance()
    {
        if ( is_null( self::$g_cStaticInstance ) || ! isset( self::$g_cStaticInstance ) )
        {
            self::$g_cStaticInstance = new self();
        }
        return self::$g_cStaticInstance;
    }



    /**
     *  GetIndex
     *  查询所有对象
     *
     *  @param $arrOutputData  {array}
     *  @param $sErroeMsg      {str}
     *  @return int
     */
    public function GetIndex( array & $arrOutputData = [], & $sErroeMsg = '' )
    {
        $this->m_sRequestType = 'Index';

        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            $result = array();
            $nRet = AfCloudStorageConst::ERROR_SUCCESS;
            $arrGet   = $this->_GetArrDataTosKey('get');

            $this->_GetDBWhereData();
            $this->_GetDBOtherData();
            $result = $this->_GetDBGetData();

            $arrResultColumn = $this->_GetTablesColumn(1);

            if( CLib::IsArrayWithKeys( $arrResultColumn ) )
            {
                $arrDisplayColumn = array_merge( $arrResultColumn, ['id','createAt','updateAt'] );

                if( array_key_exists( 'item' , $arrGet ) )
                {
                    if( 'once' == @$arrGet['item'] )
                    {
                        $result['result'] = $this->m_oDBLink->first( $arrDisplayColumn );
                    }
                    elseif( 'all' == @$arrGet['item'] )
                    {
                        $result['result'] = $this->m_oDBLink->get( $arrDisplayColumn );
                    }
                }
            }

            $arrOutputData = $result;
        }

        $this->m_arrOutputData['data'] = $arrOutputData;
        $this->m_arrOutputData['msg']  = $sErroeMsg;
        $this->_SaveLog();

        return $nRet;
    }


    /**
     *  GetShow
     *  查询一条对象
     *  @param $id             {str}
     *  @param $arrOutputData  {array}
     *  @param $sErroeMsg      {str}
     *  @return int
     */
    public function GetShow( array & $arrOutputData = [], & $sErroeMsg = '', $id )
    {
        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            $nRet = AfCloudStorageConst::ERROR_SUCCESS;

            $this->_GetDBWhereData($id);

            $arrOutputData = $this->m_oDBLink->first();
        }

        $this->m_sRequestType          = 'Show';
        $this->m_arrOutputData['data'] = $arrOutputData;
        $this->m_arrOutputData['msg']  = $sErroeMsg;
        $this->_SaveLog();

        return $nRet;
    }


    /**
     *  PostStore
     *  创建对象
     *  @param $id             {str}
     *  @param $arrOutputData  {array}
     *  @param $sErroeMsg      {str}
     *  @return int
     */
    public function PostStore( array & $arrOutputData = [], & $sErroeMsg = '', $id = '' )
    {
        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            if( $this->_SaveData( $arrOutputData, $sErroeMsg, $id ) )
            {
                $nRet = AfCloudStorageConst::ERROR_SUCCESS;
            }
            else
            {
                $nRet = AfCloudStorageConst::ERROR_ACCESS_EXEC_ERROR;
            }
        }

        if( ! empty( $id ) )
        {
            $this->m_sRequestType = 'Put';
        }
        else
        {
            $this->m_sRequestType = 'Post';
        }
        $this->m_arrOutputData['data'] = $arrOutputData;
        $this->m_arrOutputData['msg']  = $sErroeMsg;
        $this->_SaveLog();

        return $nRet;
    }


    /**
     * 删除对象
     * @param $arrOutputData
     * @param $sErroeMsg
     * @param string $id
     * @return int
     */
    public function GetDestroy( &$arrOutputData, &$sErroeMsg, $id = '' )
    {
        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            $arrId = explode('.', $id);

            $arrTablesData['updateAt'] = date('Y-m-d H:i:s', time());

            if( ! empty( $arrId[1] ) )
            {
                $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[1] ) )
                    ->delete();
            }
            else
            {
                $this->m_oDBLink->where( 'id' , $this->_GetVarType( $arrId[0] ) )
                    ->delete();
            }
            $nRet = AfCloudStorageConst::ERROR_SUCCESS;
        }

        $this->m_sRequestType          = 'Delete';
        $this->m_arrOutputData['data'] = $arrOutputData;
        $this->m_arrOutputData['msg']  = $sErroeMsg;
        $this->_SaveLog();

        return $nRet;
    }


    /**
     * 获取对应表的设置数据
     * @param string $sFlag
     * @return array
     */
    private function _GetTablesColumn( $sFlag = '' )
    {
        $arrTablesColumn = array();

        $objTablesData = DB::collection( AfCloudStorageConst::$m_str_SetupTablesNmae )
            ->where('_Table', $this->m_sDBTableName);

        // 不是管理员查看
        if( 'admin' != $this->m_sRequestForm )
        {
            $objTablesData->where('_Display', 1);
        }

        // if   $sFlag为空返回所有数据
        // else 只返回相关的字段
        if( '' == $sFlag )
        {
            $arrTablesColumn = $objTablesData->get();
        }
        else
        {
            $arrResult = $objTablesData->get( ['_Column'] );
            foreach ($arrResult as $sVal)
            {
                $arrTablesColumn[] = $sVal['_Column'];
            }
        }

        return $arrTablesColumn;
    }


    /**
     * 保存 / 修改对象
     * @param string $id
     * @return int
     */
    private function _SaveData( &$arrOutputData, &$sErroeMsg, $id = '' )
    {
        $nRet = false;

        if( '_SetupTables' == $this->m_sDBTableName )
        {
            $arrTablesData = $this->_CheckTablesData( 'setup', $arrOutputData, $sErroeMsg );
        }
        else
        {
            $arrTablesData = $this->_CheckTablesData( 'other', $arrOutputData, $sErroeMsg );
        }

        if( CLib::IsArrayWithKeys( $arrTablesData ) )
        {
            if( '' != $id )
            {
                $arrId = explode('.', $id);

                $arrTablesData['updateAt'] = date('Y-m-d H:i:s', time());

                if( ! empty( $arrId[1] ) )
                {
                    $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[1] ) )
                                    ->update( $arrTablesData );
                }
                else
                {
                    $this->m_oDBLink->where( 'id' , $this->_GetVarType( $arrId[0] ) )
                                    ->update( $arrTablesData );
                }
            }
            else
            {
                $arrTablesData['createAt'] = date('Y-m-d H:i:s', time());
                $arrTablesData['id']       = substr(md5(microtime(true).rand(0,999).rand(0,999)),8,16);
                $this->m_oDBLink->insert( $arrTablesData );
            }
            $nRet = true;
        }

        return $nRet;
    }

    /**
     * 处理用户提交数据
     * @param $sFlag
     */
    private function _CheckTablesData( $sFlag, &$arrOutputData, &$sErroeMsg  )
    {
        $arrPostData = array();

        if( 'setup' == $sFlag )
        {
            // 获取数据
            $postData    = app( 'request' )->only( AfCloudStorageConst::$m_arr_SetupTablesList );
            // 验证规则
            $arrDataRule = AfCloudStorageConst::$m_arr_SetupTablesListRule;
            // 字段描述
            $arrDataDesc = AfCloudStorageConst::$m_arr_SetupTablesListDesc;
            // 字段类型
            $arrDataType = AfCloudStorageConst::$m_arr_SetupTablesListType;
            // 默认值
            $arrDataDefault = array();
        }
        else
        {
            $arrSetOtherData = $this->_GetTablesColumn();

            foreach ( $arrSetOtherData as $sKey => $sVal)
            {
                $arrColumn[]                          = $sVal[ '_Column' ];
                $arrDataDefault[ $sVal[ '_Column' ] ] = $sVal[ '_Default' ];
                $arrDataRule   [ $sVal[ '_Column' ] ] = $sVal[ '_Verify' ];
                $arrDataDesc   [ $sVal[ '_Column' ] ] = $sVal[ '_Describe' ];
                $arrDataType   [ $sVal[ '_Column' ] ] = $sVal[ '_Type' ];
            }
            $postData = app( 'request' )->only( $arrColumn );
        }

        foreach ( $arrDataRule as $sKey => $sVal)
        {
            foreach (explode('|', $sVal) as $sSVal)
            {
                if( '' == $postData[ $sKey ] && '' != $arrDataDefault[ $sKey ] )
                {
                    $postData[ $sKey ] = $arrDataDefault[ $sKey ];
                }
                elseif( 'required' == $sSVal && '' == $postData[ $sKey ] )
                {
                    $sErroeMsg = $arrDataDesc[ $sKey ].'不能为空';
                    break 2;
                }
                elseif( 'number' == $sSVal && ! is_numeric($postData[$sKey]) )
                {
                    $sErroeMsg = $arrDataDesc[ $sKey ].'不是数字';
                    break 2;
                }
                elseif( 'phone' == $sSVal && ! preg_match('/^1[34578][0-9]{9}$/', $postData[$sKey]) )
                {
                    $sErroeMsg = $arrDataDesc[ $sKey ].'不匹配';
                    break 2;
                }
            }
            if( in_array( $arrDataType[ $sKey ] ,['str','text'] ) )
            {
                $arrPostData[ $sKey ] = strval( $postData[ $sKey ] );
            }
            elseif( in_array( $arrDataType[ $sKey ] ,['int'] ) )
            {
                $arrPostData[ $sKey ] = intval( $postData[ $sKey ] );
            }
            elseif( in_array( $arrDataType[ $sKey ] ,['file'] ) )
            {
                $arrPostData[ $sKey ] = $postData[ $sKey ];
            }
            else{
                $sErroeMsg = $arrDataDesc[ $sKey ].'类型不正确';
            }
        }
        if( '' != $sErroeMsg )
        {
            $arrPostData = array();
        }
        return $arrPostData;
    }


    /**
     * 拼查询条件返回数据(where)
     * @param string $id
     * @return array
     */
    private function _GetDBWhereData( $id = '' )
    {

        $arrWhere = $this->_GetArrDataTosKey('where');

        $conform  = [
                        'gt' => '>' ,
                        'ge' => '>=',
                        'lt' => '<' ,
                        'le' => '<=',
                        'et' => '=' ,
                        'nt' => '!=',
                        'lk' => 'like'
                    ];

        if( CLib::IsArrayWithKeys( $arrWhere ) )
        {
            foreach ( $arrWhere as $key => $val )
            {
                if( CLib::IsExistingString( $val ) )
                {
                    $this->m_oDBLink->where( $key , $this->_GetVarType( $val ) );
                }
                elseif( CLib::IsArrayWithKeys( $val ) )
                {
                    if( array_key_exists( $val[0] , $conform ) )
                    {
                        $this->m_oDBLink->where( $key , $conform[ $val[0] ], $this->_GetVarType( $val[1] ) );
                    }
                    elseif( 'in' == $val[0] )
                    {
                        $this->m_oDBLink->whereIn( $key , $val[1] );
                    }
                    elseif( 'bw' == $val[0] )
                    {
                        $this->m_oDBLink->whereBetween( $key , $val[1] );
                    }
                }
            }
        }

        if( CLib::IsExistingString( $id ) || CLib::SafeIntVal( $id ))
        {
            $arrId = explode('.', $id);

            if( ! empty( $arrId[1] ) )
            {
                $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[1] ) );
            }
            else
            {
                $this->m_oDBLink->where( 'id' , $this->_GetVarType( $arrId[0] ) );
            }
        }

        return $this->m_oDBLink;

    }

    /**
     * 拼查询条件返回数据(other)
     * @param string $id
     * @return array
     */
    private function _GetDBOtherData()
    {
        $arrOther = $this->_GetArrDataTosKey('other');

        if( CLib::IsArrayWithKeys( $arrOther ) )
        {
            if( array_key_exists( 'limit' , $arrOther ) )
            {
                if( CLib::SafeIntVal( @$arrOther['limit'][1] ) == 0 )
                {
                    $this->m_oDBLink->take( intval( $arrOther['limit'][0] ) );
                }
                else
                {
                    $this->m_oDBLink->skip( intval( $arrOther['limit'][0] ) );
                    $this->m_oDBLink->take( intval( $arrOther['limit'][1] ) );
                }
            }
            if( array_key_exists( 'order' , $arrOther ) )
            {
                if( CLib::IsExistingString( @$arrOther['order'][1] ) == 'asc' )
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'asc' );
                }
                else
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'desc' );
                }
            }
            if( array_key_exists( 'group' , $arrOther ) )
            {
                if( CLib::IsExistingString( @$arrOther['group'][0] ) )
                {
                    $this->m_oDBLink->groupBy( $arrOther['group'][0] );
                }
            }
            if( array_key_exists( 'inc' , $arrOther ) )
            {
                if( CLib::SafeIntVal( @$arrOther['inc'][1] ) != 0 )
                {
                    $this->m_oDBLink->increment( $arrOther['inc'][0] , intval( $arrOther['inc'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->increment( $arrOther['inc'][0] );
                }

            }
            if( array_key_exists( 'dec' , $arrOther ) )
            {
                if( CLib::SafeIntVal( @$arrOther['dec'][1] ) != 0 )
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'][0] , intval( $arrOther['dec'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'][0] );
                }

            }
        }

        return $this->m_oDBLink;

    }

    /**
     * 拼查询条件返回数据(get)
     * @param string $id
     * @return array
     */
    private function _GetDBGetData()
    {
        $result = array();

        $arrGet   = $this->_GetArrDataTosKey('get');

        if( CLib::IsArrayWithKeys( $arrGet ) )
        {
            if( array_key_exists( 'num' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['num'] ) )
                {
                    $result['num'] = $this->m_oDBLink->count();
                }
            }
            if( array_key_exists( 'max' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['max'] ) )
                {
                    $result['max'] = $this->m_oDBLink->max( $arrGet['max'] );
                }
            }
            if( array_key_exists( 'min' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['min'] ) )
                {
                    $result['min'] = $this->m_oDBLink->min( $arrGet['min'] );
                }
            }
            if( array_key_exists( 'avg' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['avg'] ) )
                {
                    $result['avg'] = $this->m_oDBLink->avg( $arrGet['avg'] );
                }
            }
            if( array_key_exists( 'sum' , $arrGet ) )
            {
                if( CLib::IsExistingString( @$arrGet['sum'] ) )
                {
                    $result['sum'] = $this->m_oDBLink->sum( $arrGet['sum'] );
                }
            }
        }

        return $result;
    }


    /**
     * 校验class是否存在
     * @return bool
     */
    private function _CheckStrClass(){

        $bRtn = false;

        if( false !== CLib::IsExistingString($this->m_sDBTableName) )
        {
            $bRtn = true;
        }

        return $bRtn;
    }


    /**
     * 返回格式化后的数据
     * @param $var
     * @return int|string
     */
    private function _GetVarType( $var )
    {
        if( is_numeric( $var ) )
        {
            $Retn = intval($var);
        }
        elseif( is_string( $var ) )
        {
            $Retn = strval( $var );
        }
        return $Retn;
    }

    /**
     * 判断数组中下标是否存在
     * 返回json_decode数据
     * @param $sKey
     * @return array
     */
    private function _GetArrDataTosKey( $sKey )
    {
        $arrRetn = array();

        if( CLib::IsExistingString( $sKey ) )
        {
            if( CLib::IsArrayWithKeys( $this->m_arrInputData, [ $sKey ] ) )
            {
                $arrRetn = json_decode( $this->m_arrInputData[ $sKey ] , true );
            }
        }

        return $arrRetn;
    }



    /**
     * 验证合法下标由字母组成
     * @param $arr
     * @paramType array or str
     * @return array or str
     */
    private function _IsArrKey( &$dataArrOrStr )
    {
        if(CLib::IsArrayWithKeys( $dataArrOrStr ))
        {
            foreach ($dataArrOrStr as $sKey => $vVal)
            {
                if( preg_match( '/[^\w\-]/' , $sKey ) )
                {
                    $sKey = preg_replace( '/[^\w\-]/' , '' , $sKey);

                    unset($dataArrOrStr[$sKey]);

                    if( '' != $sKey )
                    {
                        $dataArrOrStr[$sKey] = $vVal;
                    }
                }
            }
        }

        if( CLib::IsArrayWithKeys($dataArrOrStr) )
        {
            return true;
        }
        else
        {
            return false;
        }

    }


    private function _SaveLog()
    {
        DB::collection( AfCloudStorageConst::$m_str_LogTablesNmae.date('_Y-m-d-H', time()) )
            ->insert([
                        'url'        => Request::getRequestUri(),
                        'waiting'    => intval((microtime(true)-LARAVEL_START)*1000),
                        'class'      => $this->m_sDBTableName,
                        'type'       => $this->m_sRequestType,
                        'form'       => $this->m_sRequestForm,
                        'inputData'  => $this->m_arrInputData,
                        'outputData' => $this->m_arrOutputData,
                        'time'       => date('Y-m-d H:i:s', time())
            ]);
    }

}