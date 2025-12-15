<?php
/**
 * 系统配置文件
 * 用于管理系统的各种配置参数
 */

return array(
    // 调试模式开关
    'debug' => true, // true: 开启调试模式，自动登录管理员；false: 关闭调试模式，需要手动登录
    
    // 调试模式下自动登录的管理员用户ID
    'debug_user_id' => 2,
    
    // 系统名称
    'site_name' => '题库系统',
    
    // 系统描述
    'site_description' => '智能题库管理系统',
    
    // 默认每页显示题目数量
    'questions_per_page' => 20,
    
    // 最大上传文件大小（字节）
    'max_upload_size' => 10485760, // 10MB
    
    // 允许上传的文件类型
    'allowed_file_types' => array('doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'),
    
    // 数据库配置（从db.php中读取，这里不重复设置）
    // 'db' => array(
    //     'host' => 'localhost',
    //     'db_name' => 'tiku',
    //     'username' => 'root',
    //     'password' => '111111',
    //     'prefix' => 'tiku_'
    // )
);
?>