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

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var \Symfony\Component\Console\Command\Command
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    protected function setUp()
    {
        $this->application = new Application();

        if (null !== $this->command) {
            $this->application->add($this->command);

            if ($this->command instanceof ContainerAwareInterface) {
                $this->createContainerMock();
                $this->command->setContainer($this->container);
            }
        }
    }

    protected function runCommand($commandName, array $arguments = array())
    {
        $command = $this->application->find($commandName);
        $this->commandTester = new CommandTester($command);
        $input = array_merge($arguments, array('command' => $command->getName()));
        $this->commandTester->execute($input);
    }

    protected function createContainerMock()
    {
        $this->container = $this->getMock('\Symfony\Component\DependencyInjection\Container');
    }

    /**
     * @return \Xabbuh\PandaClient\Exception\ApiException|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createApiException()
    {
        return $this->getMock('\Xabbuh\PandaClient\Exception\ApiException');
    }
}
