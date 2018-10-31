<?php

namespace acfunpro\afcloudstorage;
use dekuan\delib\CLib;
use Request;
use Input;
use DB;
use Config;


/**
 *  Client of cloud storage
 */
class AfCloudStorage
{
    protected static $g_cStaticInstance;

    protected $m_oDBLink;

    protected $m_sRequestType;

    protected $m_arrOutputData;

    protected $m_sAfCloudForm;

    protected $m_sDBDriver;

    protected $m_sDBTableName;

    protected $m_arrInputData;

    protected $m_itake;

    protected $m_sRequestForm;

    protected $m_arrSelectAllow;

    public function __construct()
    {
        $this->_Init();
    }

    // 单例
    static function GetInstance( $sKey = '0' )
    {
        if ( empty( self::$g_cStaticInstance[ $sKey ] ) ||  is_null( self::$g_cStaticInstance[ $sKey ] ) || ! isset( self::$g_cStaticInstance[ $sKey ] ) )
        {
            self::$g_cStaticInstance[ $sKey ] = new self();
        }
        return self::$g_cStaticInstance[ $sKey ];
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
        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            $result = array();
            $nRet = AfCloudStorageConst::ERROR_SUCCESS;

            $arrResultColumn = $this->GetTablesColumn(true);

            if( CLib::IsArrayWithKeys( $arrResultColumn ) )
            {
                $arrGet   = $this->_GetArrDataTosKey('_afGet');

                // 拼接查询条件
                $this->_GetDBWhereData();

                // 获取sum num max min avg值
                $result = $this->_GetDBGetData();

                $this->_GetDBOtherData( $result );

                $arrDisplayColumn = array_merge( $arrResultColumn, ['_afid','createAt','updateAt'] );

                if ( CLib::IsArrayWithKeys( $arrGet, 'item' ) )
                {
                    $result['result'] = $this->m_oDBLink->first( $arrDisplayColumn );
                }
                else
                {
                    $result['result'] = $this->m_oDBLink->get( $arrDisplayColumn );
                }
            }

            $arrOutputData = $result;
        }

        $this->m_sRequestType            = 'Index';
        $this->m_arrOutputData['data']   = $arrOutputData;
        $this->m_arrOutputData['msg']    = $sErroeMsg;
        $this->m_arrOutputData['error']  = $nRet;
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
        $result = array();
        $nRet = AfCloudStorageConst::ERROR_ACCESS_CLASS_NO_EXIST;

        if( $this->_CheckStrClass() )
        {
            $nRet = AfCloudStorageConst::ERROR_SUCCESS;

            $this->_GetDBWhereData($id);

            $this->_GetDBOtherData();

            $arrResultColumn = $this->GetTablesColumn(true);

            if( CLib::IsArrayWithKeys( $arrResultColumn ) )
            {
                $arrDisplayColumn = array_merge( $arrResultColumn, ['_afid','createAt','updateAt'] );
                $arrData = $this->m_oDBLink->first( $arrDisplayColumn );

                if( CLib::IsArrayWithKeys( $arrData ) )
                {
                    $result['result'] = $arrData;
                }
            }

            $arrOutputData = $result;
        }

        $this->m_sRequestType            = 'Show';
        $this->m_arrOutputData['data']   = $arrOutputData;
        $this->m_arrOutputData['msg']    = $sErroeMsg;
        $this->m_arrOutputData['error']  = $nRet;
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

        $this->m_arrOutputData['data']   = $arrOutputData;
        $this->m_arrOutputData['msg']    = $sErroeMsg;
        $this->m_arrOutputData['error']  = $nRet;
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
            $arrId = $this->_getValue( $id );

            if( AfCloudStorageConst::$m_str_SetupTablesName != $this->m_sDBTableName || 'Index' != $this->m_sRequestForm )
            {
                $this->_GetDBWhereData();

                if( ! empty( $arrId[1] ) )
                {
                    $bStatus = $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[0], $arrId[1] ) )
                        ->delete();
                }
                else
                {
                    $bStatus = $this->m_oDBLink->where( '_afid' , $arrId[0] )
                        ->delete();
                }
                if( $bStatus )
                {
                    $nRet = AfCloudStorageConst::ERROR_SUCCESS;
                }
                else
                {
                    $sErroeMsg = '操作失败';
                }
            }
        }

        $this->m_sRequestType            = 'Delete';
        $this->m_arrOutputData['data']   = $arrOutputData;
        $this->m_arrOutputData['msg']    = $sErroeMsg;
        $this->m_arrOutputData['error']  = $nRet;
        $this->_SaveLog();

        return $nRet;
    }



    /**
     * 获取对应表的设置数据
     * @param boolean $vFlag
     * @return array
     *  $vFlag为false 返回所有数据
     *           true 只返回相关的字段
     */
    public function GetTablesColumn( $vFlag = false )
    {
        $arrTablesColumn = array();

        $objTablesData = DB::collection( AfCloudStorageConst::$m_str_SetupTablesName );

        $objTablesData->orderBy('_Sort','desc');

        if( $this->m_sDBTableName == AfCloudStorageConst::$m_str_SetupTablesName )
        {
            if( 'Admin' == $this->m_sRequestForm && true == $vFlag )
            {
                $arrTablesColumn = AfCloudStorageConst::$m_arr_SetupTablesList;
            }
        }
        else
        {
            $objTablesData->where('_Table', $this->m_sDBTableName);

            if( 'Admin' != $this->m_sRequestForm )
            {
                $objTablesData->where('_Display', 0);
            }

            if( true == $vFlag )
            {
                $arrResult = $objTablesData->get( ['_Column'] );

                foreach ($arrResult as $sVal)
                {
                    $arrTablesColumn[] = $sVal['_Column'];
                }
            }
        }

        if( false == $vFlag )
        {
            $arrTablesColumn = $objTablesData->get();
        }
        $this->m_sRequestType            = 'Column';
        $this->m_arrOutputData['data']   = $arrTablesColumn;
        $this->_SaveLog();

        return $arrTablesColumn;
    }



    /**
     * 设置参数
     * @param array $arrMData
     * @param bool $bFlag
     */
    public function SetVar( $arrMData = [], $bFlag = false )
    {
        if( array_key_exists( '_afDBTableName', $arrMData ) )
        {
            $this->m_sDBTableName = $arrMData['_afDBTableName'];
        }

        if( true == $bFlag )
        {
            $this->m_arrInputData = isset( $arrMData['_afArrInputData'] ) ? $arrMData['_afArrInputData'] : [];
        }
        else
        {
            if( CLib::IsArrayWithKeys( $arrMData, ['_afArrInputData'] ) )
            {
                $m_arrInputData = $arrMData['_afArrInputData'];

                $m_arrInputData['_afWhere'] = isset( $m_arrInputData['_afWhere'] ) ? json_decode( $m_arrInputData['_afWhere'], true ) : [];
                $m_arrInputData['_afOther'] = isset( $m_arrInputData['_afOther'] ) ? json_decode( $m_arrInputData['_afOther'], true ) : [];
                $m_arrInputData['_afGet']   = isset( $m_arrInputData['_afGet'] )   ? json_decode( $m_arrInputData['_afGet'], true ) : [];

                $this->m_arrInputData['_afWhere'] = isset( $this->m_arrInputData['_afWhere'] ) ? json_decode( $this->m_arrInputData['_afWhere'], true ) : [];
                $this->m_arrInputData['_afOther'] = isset( $this->m_arrInputData['_afOther'] ) ? json_decode( $this->m_arrInputData['_afOther'], true ) : [];
                $this->m_arrInputData['_afGet']   = isset( $this->m_arrInputData['_afGet'] )   ? json_decode( $this->m_arrInputData['_afGet'], true ) : [];

                $this->m_arrInputData['_afWhere'] = json_encode( array_merge( $this->m_arrInputData['_afWhere'], $m_arrInputData['_afWhere'] ) );
                $this->m_arrInputData['_afOther'] = json_encode( array_merge( $this->m_arrInputData['_afOther'], $m_arrInputData['_afOther'] ) );
                $this->m_arrInputData['_afGet']   = json_encode( array_merge( $this->m_arrInputData['_afGet'], $m_arrInputData['_afGet'] ) );

                unset($arrMData['_afArrInputData']['_afWhere']);
                unset($arrMData['_afArrInputData']['_afOther']);
                unset($arrMData['_afArrInputData']['_afGet']);

                $this->m_arrInputData = array_merge($this->m_arrInputData,$arrMData['_afArrInputData']);
            }
        }

        if( array_key_exists( '_afTake', $arrMData ) )
        {
            $this->m_itake        = intval( $arrMData['_afTake'] );
        }

        if( array_key_exists( '_arrSelectAllow', $arrMData ) )
        {
            $this->m_arrSelectAllow  = $arrMData['_arrSelectAllow'];
        }

        $this->_SetSomeData();

        $this->m_sRequestType            = 'SetVar';
        $this->m_arrOutputData['data']   = [ $arrMData ,$bFlag ];
        $this->_SaveLog();
    }

    /**
     * 获取参数
     * @return array
     */
    public function GetVar()
    {
        $arrRtnData = array();
        $arrRtnData['_afTake']           = $this->m_itake;
        $arrRtnData['_afRequestForm']    = $this->m_sRequestForm;
        $arrRtnData['_afDBTableName']    = $this->m_sDBTableName;
        $arrRtnData['_afArrInputData']   = $this->m_arrInputData;

        $this->m_sRequestType            = 'GetVar';
        $this->m_arrOutputData['data']   = $arrRtnData;
        $this->_SaveLog();

        return $arrRtnData;
    }


    /**
     * 获取Afid
     * @return string
     */
    public function GetAfid()
    {
        $afid     =  substr(md5(microtime().rand(0,9999).rand(0,9999)),8,16);

        if( '' != $this->m_sDBTableName )
        {
            $this->_GetDBWhereData( $afid );
            $arrCheck = $this->m_oDBLink->first();

            if( CLib::IsArrayWithKeys( $arrCheck ) )
            {
                $this->GetAfid();
            }
        }

        $this->m_sRequestType            = 'GetAfid';
        $this->m_arrOutputData['data']   = $this->m_sDBTableName.'-'.$afid;
        $this->_SaveLog();

        return $afid;
    }


    ////////////////////////////////////////////////////////////////////////////////
    //  Private
    //
    private function _Init()
    {
        // 数据过滤
        $this->m_arrInputData = Input::all();

        // 验证有效的Key(下标)
        $this->_IsArrKey($this->m_arrInputData);

        // 判断是或否为后台请求
        $this->m_sAfCloudForm = !empty( Config::get('app.afcloud.form') ) ? Config::get('app.afcloud.form') : 'admin';

        // 获取操作表名
        $this->m_sDBTableName = isset( $this->m_arrInputData['_afClass'] )?$this->m_arrInputData['_afClass']:'';

        // 获取默认分页条数
        $this->m_itake        = !empty( Config::get('app.afcloud.take') ) ? Config::get('app.afcloud.take') : 10;

        $this->_SetSomeData();
    }


    /**
     * 设置操作表名和请求来源
     */
    private function _SetSomeData()
    {
        // 判断连接数据库(目前未做mysql数据库支持)
        if( 'mysql' == $this->m_sDBDriver )
        {
            $this->m_oDBLink = DB::table( $this->m_sDBTableName );
        }
        else
        {
            $this->m_oDBLink = DB::collection($this->m_sDBTableName);
        }

        // 请求来源
        if( ! empty( $this->m_arrInputData['_afForm'] ) && $this->m_arrInputData['_afForm'] == $this->m_sAfCloudForm )
        {
            $this->m_sRequestForm = 'Admin';
        }
        else
        {
            $this->m_sRequestForm = 'Index';
        }
    }

    /**
     * 保存 / 修改对象
     * @param string $id
     * @return int
     */
    private function _SaveData( & $arrOutputData, & $sErroeMsg, $id = '' )
    {
        $nRet = false;

        $arrTablesData = array();

        if( AfCloudStorageConst::$m_str_SetupTablesName == $this->m_sDBTableName )
        {
            $arrTablesData = $this->_CheckTablesData( 'setup', $arrOutputData, $sErroeMsg, $id );
        }
        else
        {
            if( $this->_CheckTableExist() )
            {
                $arrTablesData = $this->_CheckTablesData( 'other', $arrOutputData, $sErroeMsg, $id );
            }
        }

        if( CLib::IsArrayWithKeys( $arrTablesData ) )
        {
            $this->_GetDBWhereData();

            $this->_GetDBOtherData();

            if( '' != $id )
            {
                $arrId = $this->_getValue( $id );

                $arrTablesData['updateAt'] = time();

                if( ! empty( $arrId[1] ) )
                {
                    $nRet = $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[0], $arrId[1] ) )
                        ->update( $arrTablesData );
                }
                else
                {
                    $nRet = $this->m_oDBLink->where( '_afid' , $arrId[0] )
                        ->update( $arrTablesData );
                }
            }
            else
            {
                $arrTablesData['createAt'] = time();
                $arrTablesData['_afid']    = $this->GetAfid();
                $nRet = $this->m_oDBLink->insert( $arrTablesData );

                if( $nRet )
                {
                    $arrOutputData['_afid'] = $arrTablesData['_afid'];
                }

            }

            if( ! $nRet )
            {
                $sErroeMsg = '操作失败';
            }
        }

        return $nRet;
    }


    private function _CheckTableExist()
    {
        $bRtn = false;

        $ExistTable = DB::collection( AfCloudStorageConst::$m_str_SetupTablesName )
            ->where('_Table', $this->m_sDBTableName)
            ->first();

        if( isset($ExistTable) )
        {
            $bRtn = true;
        }

        return $bRtn;
    }

    /**
     * 处理用户提交数据
     * @param $sFlag
     */
    private function _CheckTablesData( $sFlag, &$arrOutputData, &$sErroeMsg, $id  )
    {
        $arrPostData = array();
        $arrDataRule = array();
        $arrColumn   = array();

        if( 'setup' == $sFlag && 'Admin' == $this->m_sRequestForm )
        {
            // 获取数据
            $arrColumn      =  AfCloudStorageConst::$m_arr_SetupTablesList;
            // 验证规则
            $arrDataRule    = AfCloudStorageConst::$m_arr_SetupTablesListRule;
            // 字段描述
            // $arrDataDesc = AfCloudStorageConst::$m_arr_SetupTablesListDesc;
            // 字段类型
            $arrDataType    = AfCloudStorageConst::$m_arr_SetupTablesListType;
            // 默认值
            $arrDataDefault = array();
        }
        elseif( 'other' == $sFlag )
        {
            $arrSetOtherData = $this->GetTablesColumn();

            foreach ( $arrSetOtherData as $sKey => $sVal)
            {
                $arrColumn[]                          = $sVal[ '_Column' ];
                $arrDataDefault[ $sVal[ '_Column' ] ] = $sVal[ '_Default' ];
                $arrDataRule   [ $sVal[ '_Column' ] ] = $sVal[ '_Verify' ];
            //  $arrDataDesc   [ $sVal[ '_Column' ] ] = $sVal[ '_Describe' ];
                $arrDataType   [ $sVal[ '_Column' ] ] = $sVal[ '_Type' ];
            }
        }
        else
        {
            $sErroeMsg   = '错误请求';
        }

        if( CLib::IsArrayWithKeys( $arrColumn ) )
        {
            foreach( $arrColumn as $sCv )
            {
                if( '' != $id )
                {
                    if( isset( $this->m_arrInputData[$sCv] ) )
                    {
                        $arrPostData[$sCv] = $this->m_arrInputData[$sCv];
                    }
                    else
                    {
                        unset( $arrDataRule[$sCv] );
                    }
                }
                else
                {
                    $arrPostData[$sCv] = isset( $this->m_arrInputData[$sCv] ) ? $this->m_arrInputData[$sCv] : '';
                }
            }

            // 校验字段验证信息
            foreach ($arrDataRule as $sSKey => $arrVal)
            {
                foreach ($arrVal as $sKey => $sVal)
                {
                    // 唯一验证
                    if( 'unique' == $sVal )
                    {
                        if( AfCloudStorageConst::$m_str_SetupTablesName != $this->m_sDBTableName )
                        {
                            $arrDataRule[$sSKey][$sKey] = "unique:$this->m_sDBTableName,$sSKey";

                            if( '' != $id )
                            {
                                $arrId = $this->_getValue( $id );

                                if( ! empty( $arrId[1] ) )
                                {
                                    $arrDataRule[$sSKey][$sKey] .= ",$arrId[1],$arrId[0]";
                                }
                                else
                                {
                                    $arrDataRule[$sSKey][$sKey] .= ",$arrId[0],_afid";
                                }
                            }
                        }
                        else
                        {
                            if( !empty( $arrPostData['_Table'] ) && !empty( $arrPostData['_Column'] ) )
                            {
                                $objQuery = DB::collection( AfCloudStorageConst::$m_str_SetupTablesName)
                                                ->where('_Table',  $arrPostData['_Table'])
                                                ->where('_Column', $arrPostData['_Column']);

                                if( '' != $id )
                                {
                                    $arrId = $this->_getValue( $id );

                                    if( ! empty( $arrId[1] ) )
                                    {
                                        $objQuery->where($arrId[0], '!=', $arrId[1]);
                                    }
                                    else
                                    {
                                        $objQuery->where('_afid', '!=', $arrId[0]);
                                    }
                                }

                                if($objQuery->count() > 0)
                                {
                                    $sErroeMsg = "The _Column has already been taken.";
                                    break 2;
                                }
                                unset($arrDataRule[$sSKey]);
                            }
                            else
                            {
                                $sErroeMsg = '错误请求';
                            }
                        }
                    }
                }
            }

            if( '' == $sErroeMsg)
            {
                foreach ($arrPostData as $sKey => $vVal)
                {
                    if ('' != @$arrDataDefault[$sKey] && '' == $vVal)
                    {
                        $arrPostData[$sKey] = $arrDataDefault[$sKey];
                    }
                }

                $validator   = app( 'validator' )->make( $arrPostData, $arrDataRule );

                if( $validator->passes() )
                {
                    // 转换字段类型
                    foreach ($arrPostData as $sKey => $vVal)
                    {
                        // 类型验证
                        if( in_array( $arrDataType[ $sKey ] ,AfCloudStorageConst::$m_arr_StrData ) )
                        {
                            $arrPostData[ $sKey ] = strval( $arrPostData[ $sKey ] );
                        }
                        elseif( in_array( $arrDataType[ $sKey ] ,AfCloudStorageConst::$m_arr_IntData ) )
                        {
                            $arrPostData[ $sKey ] = intval( $arrPostData[ $sKey ] );
                        }
                        elseif( in_array( $arrDataType[ $sKey ] ,AfCloudStorageConst::$m_arr_ArrData ) )
                        {
                            if( ! is_array($arrPostData[ $sKey ]) )
                            {
                                $arrPostData[ $sKey ] = [ $arrPostData[ $sKey ] ];
                            }
                            else
                            {
                                $arrPostData[ $sKey ] = $arrPostData[ $sKey ];
                            }
                        }
                        else
                        {
                            $arrPostData[ $sKey ] = strval( $arrPostData[ $sKey ] );
                        }
                    }
                }
                else
                {
                    $sErroeMsg = $validator->messages()->first();
                }
            }

            if( '' != $sErroeMsg)
            {
                $arrPostData = array();
            }
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
        $arrWhere = $this->_GetArrDataTosKey('_afWhere');

        $conform  = [
            'gt' => '>' ,
            'ge' => '>=',
            'lt' => '<' ,
            'le' => '<=',
            'eq' => '=' ,
            'ne' => '!=',
            'lk' => 'like'
        ];

        if( CLib::IsArrayWithKeys( $arrWhere ) )
        {
            foreach ( $arrWhere as $key => $val )
            {
                if( ! CLib::IsArrayWithKeys( $val ) )
                {
                    $this->m_oDBLink->where( $key , $this->_GetVarType( $key, $val ) );
                }
                elseif( '_or' == $key )
                {
                    foreach ( $val as $sKey => $sVal  )
                    {
                        if( CLib::IsArrayWithKeys( $sVal ) && array_key_exists( $sVal[0] , $conform ) )
                        {
                            $this->m_oDBLink->orWhere( $sKey , $conform[ $sVal[0] ], $this->_GetVarType( $sKey, $sVal[1] ) );
                        }
                        elseif ( ! CLib::IsArrayWithKeys( $sVal ) )
                        {
                            $this->m_oDBLink->orWhere( $sKey , $this->_GetVarType( $sKey, $sVal ) );
                        }
                    }
                }
                elseif( CLib::IsArrayWithKeys( $val ) )
                {
                    if( CLib::IsExistingString( $val[0] ) && array_key_exists( $val[0] , $conform ) )
                    {
                        $this->m_oDBLink->where( $key , $conform[ $val[0] ], $this->_GetVarType( $key, $val[1] ) );
                    }
                    elseif( 'in' == $val[0] )
                    {
                        $this->m_oDBLink->whereIn( $key , $val[1] );
                    }
                    elseif( 'nin' == $val[0] )
                    {
                        $this->m_oDBLink->whereNotIn( $key , $val[1] );
                    }
                    elseif( 'bw' == $val[0] )
                    {
                        $this->m_oDBLink->whereBetween( $key , $val[1] );
                    }
                    elseif( 'nbw' == $val[0] )
                    {
                        $this->m_oDBLink->whereNotBetween( $key , $val[1] );
                    }
                }
            }
        }

        if( CLib::IsExistingString( $id ) || CLib::SafeIntVal( $id ))
        {
            $arrId = $this->_getValue( $id );

            if( ! empty( $arrId[1] ) )
            {
                $this->m_oDBLink->where( $arrId[0] , $this->_GetVarType( $arrId[0], $arrId[1] ) );
            }
            else
            {
                $this->m_oDBLink->where( '_afid' , $arrId[0] );
            }
        }

        return $this->m_oDBLink;
    }

    /**
     * 拼查询条件返回数据(other)
     * @param string $id
     * @return array
     */
    private function _GetDBOtherData( & $result = [] )
    {
        $arrOther = $this->_GetArrDataTosKey('_afOther');


        if( array_key_exists( 'order' , $arrOther ) )
        {
            if( CLib::IsArrayWithKeys( $arrOther['order'] ) )
            {
                if( 'asc' == $arrOther['order'][1] )
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'asc' );
                }
                else
                {
                    $this->m_oDBLink->orderBy( $arrOther['order'][0] , 'desc' );
                }
            }
            else
            {
                $this->m_oDBLink->orderBy( $arrOther['order'] , 'desc' );
            }
        }

        if( array_key_exists( 'group' , $arrOther ) )
        {
            if( CLib::IsExistingString( $arrOther['group'] ) )
            {
                $this->m_oDBLink->groupBy( $arrOther['group'] );
            }
        }

        if( array_key_exists( 'num' , $arrOther ) )
        {
            if( CLib::IsExistingString( @$arrOther['num'] ) )
            {
                $arrResultColumn = $this->GetTablesColumn(true);

                if( CLib::IsArrayWithKeys( $arrResultColumn ) )
                {
                    $arrDisplayColumn = array_merge( $arrResultColumn, ['_afid','createAt','updateAt'] );

                    $result['num'] = count( $this->m_oDBLink->get( $arrDisplayColumn ) );
                }
            }
        }

        if( ! array_key_exists( 'limit' , $arrOther ) )
        {
            $this->m_oDBLink->take( $this->m_itake );
        }

        if( CLib::IsArrayWithKeys( $arrOther ) )
        {
            if( array_key_exists( 'limit' , $arrOther ) )
            {
                if( 'all' != $arrOther['limit'] )
                {
                    if( CLib::IsArrayWithKeys( $arrOther['limit'] ) )
                    {
                        $this->m_oDBLink->skip( intval( $arrOther['limit'][0] ) );
                        $this->m_oDBLink->take( intval( $arrOther['limit'][1] ) );
                    }
                    else
                    {
                        $this->m_oDBLink->take( intval( $arrOther['limit'] ) );
                    }
                }
            }

            if( array_key_exists( 'inc' , $arrOther ) )
            {
                if( CLib::IsArrayWithKeys( $arrOther['inc'] ) )
                {
                    $this->m_oDBLink->increment( $arrOther['inc'][0] , intval( $arrOther['inc'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->increment( $arrOther['inc'] );
                }

            }

            if( array_key_exists( 'dec' , $arrOther ) )
            {
                if( CLib::IsArrayWithKeys( $arrOther['dec'] ) )
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'][0] , intval( $arrOther['dec'][1] ) );
                }
                else
                {
                    $this->m_oDBLink->decrement( $arrOther['dec'] );
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

        $arrGet   = $this->_GetArrDataTosKey('_afGet');

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
    private function _GetVarType( $Column = '', $var = '' )
    {
        $RVal = '';

        if( AfCloudStorageConst::$m_str_SetupTablesName == $this->m_sDBTableName )
        {
            $result = AfCloudStorageConst::$m_arr_SetupTablesListType;
        }
        else
        {
            $result = DB::collection( AfCloudStorageConst::$m_str_SetupTablesName )
                            ->where('_Table',$this->m_sDBTableName)
                            ->where('_Column', $Column)
                            ->first();
        }

        if( !empty( $result ) )
        {
            // 类型验证
            if( in_array( $result[ '_Type' ] ,AfCloudStorageConst::$m_arr_StrData ) )
            {
                $RVal = strval( $var );
            }
            elseif( in_array( $result[ '_Type' ] ,AfCloudStorageConst::$m_arr_IntData ) )
            {
                $RVal = intval( $var );
            }
            else
            {
                $RVal = strval( $var );
            }
        }
        else
        {
            $RVal = strval( $var );
        }

        return $RVal;
    }

    /**
     * 判断数组中下标是否存在
     * 返回json_decode数据
     * @param $sKey
     * @return array
     */
    private function _GetArrDataTosKey( $sKey )
    {
        if ( ! CLib::IsExistingString( $sKey ) )
        {
            return [];
        }

        $arrRetn = array();

        if( CLib::IsArrayWithKeys( $this->m_arrInputData, [ $sKey ] ) )
        {
            $arrData = json_decode( $this->m_arrInputData[ $sKey ] , true );

            if( CLib::IsArrayWithKeys( $arrData ))
            {
                foreach ( $arrData as $sSKey => $sSVal )
                {
                    if( isset( $this->m_arrSelectAllow[$sKey] ) && ! in_array( $sSKey, $this->m_arrSelectAllow[$sKey] ) )
                    {
                        unset( $arrData[$sSKey] );
                    }
                }
                $arrRetn = $arrData;
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
        $bRtn = false;

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
            $bRtn = true;
        }

        return $bRtn;
    }

    private function _getValue( $id )
    {
        $arrId = [];

        $first = stripos($id ,'`');
        $last = strripos($id ,'`');


        // 参数为  `$id` 不做拆解
        if($first == 0 && $last+1 == strlen($id))
        {
            $arrId[0] = substr($id,1,strlen($id)-2);
        }
        else
        {
            $len = strpos( $id, '.' );

            // 参数中不存在  .  不做拆解
            if( $len === false ){
                $arrId[0] = $id;
            }
            else
            {
            // 拆解参数
                $arrId[0] = substr( $id, 0, $len );
                $arrId[1] = substr( $id, $len + 1 );
            }
        }

        return $arrId;
    }


    private function _SaveLog()
    {
        return;
        DB::collection( AfCloudStorageConst::$m_str_LogTablesName.date('_Y-m-d', time()) )
            ->insert([
                'ip'         => CLib::GetClientIP( false, false ),
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
