<?php

namespace acfunpro\afcloudstorage;

/*
 *      CCloudStorageConst
 */
class AfCloudStorageConst
{
    static public $m_str_LogTablesName   = '_LogTables';
    static public $m_str_SetupTablesName = '_SetupTables';
    static public $m_arr_SetupTablesList = array(
        '_Table',      // 表名
        '_Column',     // 列名
        '_Type',       // 类型
        '_Default',    // 默认值
        '_Describe',   // 描述
        '_Verify',     // 验证规则
        '_Sort',       // 排序
        '_Tag',        // 标签
        '_Display',    // 客户端不可见
    );
    static public $m_arr_SetupTablesListType = array(
        '_Table'      =>    'str',    // 表名
        '_Column'     =>    'str',    // 列名
        '_Type'       =>    'str',    // 类型
        '_Default'    =>    'str',    // 默认值
        '_Describe'   =>    'str',    // 描述
        '_Verify'     =>    'array',  // 验证规则
        '_Sort'       =>    'int',    // 排序
        '_Tag'        =>    'str',    // 标签
        '_Display'    =>    'int',    // 客户端不可见
    );
    static public $m_arr_SetupTablesListRule = array(
        '_Table'      =>    ['required'],           // 表名
        '_Column'     =>    ['required','unique'],  // 列名
        '_Type'       =>    ['required'],           // 类型
        '_Default'    =>    [],                     // 默认值
        '_Describe'   =>    ['required'],           // 描述
        '_Verify'     =>    [],                     // 验证规则
        '_Sort'       =>    ['integer'],            // 排序
        '_Tag'        =>    [],                     // 标签
        '_Display'    =>    ['integer'],            // 客户端不可见
    );
    static public $m_arr_SetupTablesListDesc = array(
        '_Table'      =>    '表名',        // 表名
        '_Column'     =>    '列名',        // 列名
        '_Type'       =>    '类型',        // 类型
        '_Default'    =>    '默认值',      // 默认值
        '_Describe'   =>    '描述',        // 描述
        '_Verify'     =>    '验证规则',     // 验证规则
        '_Sort'       =>    '排序',        // 排序
        '_Tag'        =>    '标签',        // 标签
        '_Display'    =>    '客户端不可见',  // 客户端不可见
    );

    // 转换字段类型
    static public $m_arr_StrData = array(
        'str',
        'text'
    );
    static public $m_arr_IntData = array(
        'int'
    );
    static public $m_arr_ArrData = array(
        'array',
        'file'
    );
	//
	//	common error codes
	//
	const ERROR_SUCCESS			            = 0;            //      successfully
    const ERROR_ACCESS_CLASS_NO_EXIST		= -100001;      //      access class no exist
    const ERROR_ACCESS_EXEC_ERROR		    = -100010;      //      access exec error

}

?>