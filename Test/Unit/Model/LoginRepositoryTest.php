<?php
/**
 * MageOS
 *
 * @category   MageOS
 * @package    MageOS_AdminActivityLog
 * @copyright  Copyright (C) 2018 Kiwi Commerce Ltd (https://kiwicommerce.co.uk/)
 * @copyright  Copyright (C) 2025 MageOS (https://mage-os.org/)
 * @license    https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace MageOS\AdminActivityLog\Test\Unit\Model;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\User\Model\User;
use MageOS\AdminActivityLog\Model\Handler;
use MageOS\AdminActivityLog\Model\Login;
use MageOS\AdminActivityLog\Model\LoginFactory;
use MageOS\AdminActivityLog\Model\LoginRepository;
use MageOS\AdminActivityLog\Model\Processor;
use MageOS\AdminActivityLog\Model\ResourceModel\Login\Collection;
use MageOS\AdminActivityLog\Model\ResourceModel\Login\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginRepositoryTest extends TestCase
{
    private LoginFactory&MockObject $loginFactory;
    private CollectionFactory&MockObject $collectionFactory;
    private Processor&MockObject $processor;
    private LoginRepository $repository;

    private RemoteAddress&MockObject $remoteAddress;
    private HttpRequest&MockObject $request;
    private Handler&MockObject $handler;
    private Header&MockObject $header;

    protected function setUp(): void
    {
        $this->loginFactory = $this->createMock(LoginFactory::class);
        $this->collectionFactory = $this->createMock(CollectionFactory::class);
        $this->processor = $this->createMock(Processor::class);

        $this->setupProcessorMocks();

        $this->repository = new LoginRepository(
            $this->loginFactory,
            $this->collectionFactory,
            $this->processor
        );
    }

    private function setupProcessorMocks(): void
    {
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('192.168.1.1');

        $this->request = $this->createMock(HttpRequest::class);
        $this->request->method('getServer')->with('HTTP_X_FORWARDED_FOR')->willReturn('10.0.0.1');

        $this->header = $this->createMock(Header::class);
        $this->header->method('getHttpUserAgent')->willReturn('Mozilla/5.0 TestBrowser');

        $this->handler = $this->createMock(Handler::class);
        $this->handler->method('getHeader')->willReturn($this->header);

        $this->processor->method('getRemoteAddress')->willReturn($this->remoteAddress);
        $this->processor->method('getRequest')->willReturn($this->request);
        $this->processor->method('sanitizeForwardedIp')->with('10.0.0.1')->willReturn('10.0.0.1');
        $this->processor->method('getHandler')->willReturn($this->handler);
    }

    private function createLoginMock(): Login&MockObject
    {
        return $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setAdminId',
                'setUsername',
                'setName',
                'setRemoteIp',
                'setForwardedIp',
                'setUserAgent',
                'setStatus',
                'setType',
                'setRemarks',
                'save',
            ])
            ->getMock();
    }

    public function testSetUserReturnsSelf(): void
    {
        $user = $this->createMock(User::class);

        $result = $this->repository->setUser($user);

        $this->assertSame($this->repository, $result);
    }

    public function testAddLogCreatesLoginWithUserData(): void
    {
        $loginMock = $this->createLoginMock();

        $this->loginFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($loginMock);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);
        $user->method('getUsername')->willReturn('admin.user');
        $user->method('getName')->willReturn('john doe');

        $this->repository->setUser($user);

        $loginMock->expects($this->once())->method('setAdminId')->with(42);
        $loginMock->expects($this->once())->method('setUsername')->with('admin.user');
        $loginMock->expects($this->once())->method('setName')->with('John Doe');
        $loginMock->expects($this->once())->method('setRemoteIp')->with('192.168.1.1');
        $loginMock->expects($this->once())->method('setForwardedIp')->with('10.0.0.1');
        $loginMock->expects($this->once())->method('setUserAgent')->with('Mozilla/5.0 TestBrowser');
        $loginMock->expects($this->once())->method('setStatus')->with(true);
        $loginMock->expects($this->once())->method('setType')->with('Login');
        $loginMock->expects($this->once())->method('setRemarks')->with('');
        $loginMock->expects($this->once())->method('save');

        $result = $this->repository->addLog(LoginRepository::LOGIN_SUCCESS, 'Login');

        $this->assertTrue($result);
    }

    public function testAddLogWithoutUserSkipsUserFields(): void
    {
        $loginMock = $this->createLoginMock();

        $this->loginFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($loginMock);

        $loginMock->expects($this->never())->method('setAdminId');
        $loginMock->expects($this->never())->method('setUsername');
        $loginMock->expects($this->never())->method('setName');
        $loginMock->expects($this->once())->method('setRemoteIp')->with('192.168.1.1');
        $loginMock->expects($this->once())->method('setStatus')->with(true);
        $loginMock->expects($this->once())->method('setType')->with('Login');
        $loginMock->expects($this->once())->method('save');

        $result = $this->repository->addLog(LoginRepository::LOGIN_SUCCESS, 'Login');

        $this->assertTrue($result);
    }

    public function testAddSuccessLogCallsAddLogWithCorrectParams(): void
    {
        $loginMock = $this->createLoginMock();

        $this->loginFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($loginMock);

        $loginMock->expects($this->once())->method('setStatus')->with(true);
        $loginMock->expects($this->once())->method('setType')->with('Login');
        $loginMock->expects($this->once())->method('setRemarks')->with('');
        $loginMock->expects($this->once())->method('save');

        $this->repository->addSuccessLog();
    }

    public function testAddFailedLogCallsAddLogWithRemarkAndFailedStatus(): void
    {
        $loginMock = $this->createLoginMock();

        $this->loginFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($loginMock);

        $loginMock->expects($this->once())->method('setStatus')->with(false);
        $loginMock->expects($this->once())->method('setType')->with('Login');
        $loginMock->expects($this->once())->method('setRemarks')->with('Invalid password');
        $loginMock->expects($this->once())->method('save');

        $this->repository->addFailedLog('Invalid password');
    }

    public function testAddLogoutLogCallsAddLogWithLogoutType(): void
    {
        $loginMock = $this->createLoginMock();

        $this->loginFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($loginMock);

        $loginMock->expects($this->once())->method('setStatus')->with(true);
        $loginMock->expects($this->once())->method('setType')->with('Logout');
        $loginMock->expects($this->once())->method('save');

        $this->repository->addLogoutLog();
    }

    public function testGetListBeforeDateReturnsFilteredCollection(): void
    {
        $endDate = '2024-12-31 23:59:59';
        $mockCollection = $this->createMock(Collection::class);

        $this->collectionFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        $mockCollection
            ->expects($this->once())
            ->method('addFieldToSelect')
            ->with('entity_id')
            ->willReturnSelf();

        $mockCollection
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('created_at', ['lteq' => $endDate])
            ->willReturnSelf();

        $result = $this->repository->getListBeforeDate($endDate);

        $this->assertSame($mockCollection, $result);
    }
}
