<?php

namespace Eloquent\Composer\NpmBridge;

use Eloquent\Phony\Phpunit\Phony;
use PHPUnit\Framework\TestCase;

class NpmClientTest extends TestCase
{
    protected function setUp()
    {
        $this->processExecutor = Phony::mock('Composer\Util\ProcessExecutor');
        $this->executableFinder = Phony::mock('Symfony\Component\Process\ExecutableFinder');
        $this->getcwd = Phony::stub();
        $this->chdir = Phony::stub();
        $this->client =
            new NpmClient($this->processExecutor->get(), $this->executableFinder->get(), $this->getcwd, $this->chdir);

        $this->processExecutor->execute->returns(0);
        Phony::onStatic($this->processExecutor)->getTimeout->returns(300);
        Phony::onStatic($this->processExecutor)->setTimeout->returns();
        $this->executableFinder->find->with('npm')->returns('/path/to/npm');
        $this->getcwd->returns('/path/to/cwd');
    }

    public function testInstall()
    {
        $this->assertNull($this->client->install());
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'install'")
        );
        $this->chdir->never()->returned();
        $this->processExecutor->setTimeout->never()->returned();
    }

    public function testInstallWorking()
    {
        $this->assertNull($this->client->install('/path/to/project'));
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            $this->chdir->calledWith('/path/to/project'),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'install'"),
            $this->chdir->calledWith('/path/to/cwd')
        );
        $this->processExecutor->setTimeout->never()->returned();
    }

    public function testInstallTimeout()
    {
        $this->assertNull($this->client->setTimeout(900));
        $this->assertNull($this->client->install());
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            Phony::onStatic($this->processExecutor)->getTimeout->calledWith(),
            Phony::onStatic($this->processExecutor)->setTimeout->calledWith(900),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'install'"),
            Phony::onStatic($this->processExecutor)->setTimeout->calledWith(300)
        );
        $this->chdir->never()->returned();
    }

    public function testInstallProductionMode()
    {
        $this->assertNull($this->client->install('/path/to/project', false));
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            $this->chdir->calledWith('/path/to/project'),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'install' '--production'"),
            $this->chdir->calledWith('/path/to/cwd')
        );
    }

    public function testInstallFailureNpmNotFound()
    {
        $this->executableFinder->find->with('npm')->returns(null);

        $this->expectException('Eloquent\Composer\NpmBridge\Exception\NpmNotFoundException');
        $this->client->install('/path/to/project');
    }

    public function testInstallFailureCommandFailed()
    {
        $this->processExecutor->execute->returns(1);

        $this->expectException('Eloquent\Composer\NpmBridge\Exception\NpmCommandFailedException');
        $this->client->install('/path/to/project');
    }

    public function testUpdate()
    {
        $this->assertNull($this->client->update());
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'update'")
        );
        $this->chdir->never()->returned();
        $this->processExecutor->setTimeout->never()->returned();
    }

    public function testUpdateWorking()
    {
        $this->assertNull($this->client->update('/path/to/project'));
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            $this->chdir->calledWith('/path/to/project'),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'update'"),
            $this->chdir->calledWith('/path/to/cwd')
        );
        $this->processExecutor->setTimeout->never()->returned();
    }

    public function testUpdateTimeout()
    {
        $this->assertNull($this->client->setTimeout(900));
        $this->assertNull($this->client->update());
        Phony::inOrder(
            $this->executableFinder->find->calledWith('npm'),
            Phony::onStatic($this->processExecutor)->getTimeout->calledWith(),
            Phony::onStatic($this->processExecutor)->setTimeout->calledWith(900),
            $this->processExecutor->execute->calledWith("'/path/to/npm' 'update'"),
            Phony::onStatic($this->processExecutor)->setTimeout->calledWith(300)
        );
        $this->chdir->never()->returned();
    }

    public function testUpdateFailureNpmNotFound()
    {
        $this->executableFinder->find->with('npm')->returns(null);

        $this->expectException('Eloquent\Composer\NpmBridge\Exception\NpmNotFoundException');
        $this->client->update('/path/to/project');
    }

    public function testUpdateFailureCommandFailed()
    {
        $this->processExecutor->execute->returns(1);

        $this->expectException('Eloquent\Composer\NpmBridge\Exception\NpmCommandFailedException');
        $this->client->update('/path/to/project');
    }

    public function testValid()
    {
        $this->executableFinder->find->with('npm')->returns(null);
        $this->assertSame(
            false,
            $this->client->valid()
        );

        $this->executableFinder->find->with('npm')->returns('/path/to/npm');
        $this->assertSame(
            true,
            $this->client->valid()
        );
    }
}
