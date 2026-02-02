<?php

if (!function_exists('activity_label')) {
    function activity_label(string $action): string
    {
        return match ($action) {
            'CREATE_WORKSPACE' => 'สร้าง Workspace',
            'CREATE_PROJECT'   => 'สร้าง Project',
            'CREATE_TASK'      => 'สร้างงาน',
            'MOVE_TASK'        => 'ย้ายสถานะงาน',
            'UPDATE_COMMENT' => 'แก้ไขคอมเมนต์',
            'DELETE_COMMENT' => 'ลบคอมเมนต์',
            'ADD_COMMENT' => 'เพิ่มคอมเมนต์',
            'ADD_ATTACHMENT' => 'แนบไฟล์',
            'DELETE_ATTACHMENT' => 'ลบไฟล์แนบ',
            'INVITE_MEMBER' => 'เชิญสมาชิก',
            'CHANGE_MEMBER_ROLE' => 'เปลี่ยนสิทธิ์สมาชิก',
            'REMOVE_MEMBER' => 'ลบสมาชิกออกจากโปรเจกต์',
            'LEAVE_PROJECT' => 'ออกจากโปรเจกต์',
            'ASSIGN_TASK' => 'มอบหมายงาน',
            default            => $action,
        };
    }
}
