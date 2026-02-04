<?php

if (!function_exists('activity_label')) {
    function activity_label(string $action): string
    {
        return [
            'CREATE_WORKSPACE'     => 'สร้างเวิร์กสเปซ',
            'CREATE_PROJECT'       => 'สร้างโปรเจกต์',
            'CREATE_TASK'          => 'สร้างงาน',
            'UPDATE_TASK'          => 'แก้ไขงาน',
            'DELETE_TASK'          => 'ลบงาน',

            'MOVE_TASK'            => 'ย้ายสถานะงาน',
            'SET_PRIORITY'         => 'ตั้งค่า Priority',
            'ASSIGN_TASK'          => 'มอบหมายงาน',

            'ADD_COMMENT'          => 'เพิ่มคอมเมนต์',
            'UPDATE_COMMENT'       => 'แก้ไขคอมเมนต์',
            'DELETE_COMMENT'       => 'ลบคอมเมนต์',

            'ADD_ATTACHMENT'       => 'แนบไฟล์',
            'DELETE_ATTACHMENT'    => 'ลบไฟล์',

            'INVITE_MEMBER'        => 'เชิญสมาชิกเข้าร่วม',
            'CHANGE_MEMBER_ROLE'   => 'เปลี่ยนสิทธิ์สมาชิก',
            'REMOVE_MEMBER'        => 'ลบสมาชิก',
            'LEAVE_PROJECT'        => 'ออกจากโปรเจกต์',

            'ACCEPT_INVITE'        => 'ยอมรับคำเชิญ',
            'DECLINE_INVITE'       => 'ปฏิเสธคำเชิญ',
        ][$action] ?? $action;
    }
}
