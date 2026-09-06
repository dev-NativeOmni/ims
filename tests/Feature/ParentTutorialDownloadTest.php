<?php

namespace Tests\Feature;

use Tests\TestCase;

class ParentTutorialDownloadTest extends TestCase
{
    public function test_can_view_tutorial_html_page(): void
    {
        $response = $this->get('/panduan-video-orangtua');
        $response->assertStatus(200);
    }

    public function test_can_download_vo_txt_file(): void
    {
        $response = $this->get('/panduan-video-orangtua/download-vo');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_can_download_script_md_file(): void
    {
        $response = $this->get('/panduan-video-orangtua/download-script');
        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }
}
