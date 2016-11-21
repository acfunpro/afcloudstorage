<?php

namespace acfunpro\afcloudstorage;
use dekuan\delib\CLib;
use dekuan\vdata\CConst;
use Input;
use DB;


/**
 *  Client of cloud storage
 */
class AfCloudStorage
{
    protected static $g_cStaticInstance;

    public $DBDriver;

    protected $DBLink;

    public $arrInputData;

    public function __construct()
    {

        $this->arrInputData = clean( Input::all() , array('Attr.EnableID' => true) );

        $this->_IsArrKey($this->arrInputData);

        if($this->DBDriver == 'mysql')
        {
            $this->DBLink = DB::table( Input::get('class') );
        }
        else
        {
            $this->DBLink = DB::collection( Input::get('class') );
        }

    }

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
     *  @param $arrInputData   {array}
     *  @param $arrOutputData  {array}
     *  @param $sErroeMsg      {str}
     *  @return int
     */
    public function GetIndex( array & $arrOutputData = [], & $sErroeMsg = '' )
    {

        $nRet = CConst::ERROR_SUCCESS;

        $arrOutputData = Input::all();

        return $nRet;

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
            foreach ($dataArrOrStr as $key => $val)
            {
                if( preg_match( '/[^\w\-]/' , $key ) )
                {
                    $sKey = preg_replace( '/[^\w\-]/' , '' , $key);

                    unset($dataArrOrStr[$key]);

                    if( '' != $sKey )
                    {
                        $dataArrOrStr[$sKey] = $val;
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


    private function _GetDBData()
    {

        $arrWhere = json_decode( @$this->arrInputData['where'] , true );

        $arrOther = json_decode( @$this->arrInputData['other'] , true );

        if( CLib::IsArrayWithKeys( $arrWhere ) )
        {
            foreach ( $arrWhere as $key => $val )
            {
                if( CLib::IsExistingString( $val ) )
                {
                    $this->DBLink->where( $key , $val );
                }
                elseif( CLib::IsArrayWithKeys( $val ) )
                {
                    if( in_array( $val[0] , [ '<' , '<=' , '>' , '>=' , '=' , '!=' , 'like' ] ) )
                    {
                        $this->DBLink->where( $key , $val[0], $val[1] );
                    }
                    elseif( $val[0] == 'in' )
                    {
                        $this->DBLink->whereIn( $key , $val[1] );
                    }
                    elseif( $val[0] == 'bw' )
                    {
                        $this->DBLink->whereBetween( $key , $val[1] );
                    }

                }
            }
        }

        if( CLib::IsArrayWithKeys( $arrOther ) )
        {
            if( array_key_exists( 'limit' , $arrOther ) )
            {
                if( CLib::SafeIntVal( @$arrOther['limit'][1] ) == 0 )
                {
                    $this->DBLink->skip( intval( $arrOther['limit'][0] ) );
                }
                else
                {
                    $this->DBLink->skip( intval( $arrOther['limit'][0] ) );
                    $this->DBLink->take( intval( $arrOther['limit'][1] ) );
                }
            }
        }

        return $this->DBLink->get();
    }
}
