<?php

/*
 * This file is part of the XabbuhPandaBundle package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\PandaBundle\Tests\Command;

use Xabbuh\PandaBundle\Command\ListVideosCommand;
use Xabbuh\PandaClient\Model\Video;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class ListVideosCommandTest extends CloudCommandTest
{
    protected function setUp()
    {
        $this->command = new ListVideosCommand();

        parent::setUp();
    }

    public function testCommandWithoutOptions()
    {
        $this->defaultCloud
            ->expects($this->once())
            ->method('getVideosForPagination')
            ->with(1, 10)
            ->will($this->returnValue($this->createVideoResultList(1, 10)));
        $this->runCommand('panda:video:list');
        $this->validateVideoResultOutput(1);
    }

    public function testCommandWithPage()
    {
        $this->defaultCloud
            ->expects($this->once())
            ->method('getVideosForPagination')
            ->with(3, 10)
            ->will($this->returnValue($this->createVideoResultList(3, 10)));
        $this->runCommand('panda:video:list', array('--page' => 3));
        $this->validateVideoResultOutput(3);
    }

    public function testCommandWithPerPage()
    {
        $this->defaultCloud
            ->expects($this->once())
            ->method('getVideosForPagination')
            ->with(1, 5)
            ->will($this->returnValue($this->createVideoResultList(1, 5)));
        $this->runCommand('panda:video:list', array('--per-page' => 5));
        $this->validateVideoResultOutput(1);
    }

    private function createVideoResultList($page, $perPage)
    {
        $video1 = new Video();
        $video1->setId('video-1');
        $video1->setStatus('success');
        $video2 = new Video();
        $video2->setId('video-2');
        $video2->setStatus('fail');
        $video3 = new Video();
        $video3->setId('video-3');
        $video3->setStatus('processing');

        $result = new \stdClass();
        $result->videos = array($video1, $video2, $video3);
        $result->page = $page;
        $result->per_page = $perPage;
        $result->total = 3;

        return $result;
    }

    private function validateVideoResultOutput($page)
    {
        $this->assertRegExp(
            '/Page '.$page.' of 1/',
            $this->commandTester->getDisplay()
        );
        $this->assertRegExp(
            '/Total number of videos: 3/',
            $this->commandTester->getDisplay()
        );
        $this->assertRegExp(
            '/video-1\s*\|\s*success/',
            $this->commandTester->getDisplay()
        );
        $this->assertRegExp(
            '/video-2\s*\|\s*fail/',
            $this->commandTester->getDisplay()
        );
        $this->assertRegExp(
            '/video-3\s*\|\s*processing/',
            $this->commandTester->getDisplay()
        );
    }
}
