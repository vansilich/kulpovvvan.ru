<?php

namespace Tests\Unit\Jobs;

use App\Helpers\Api\Mailganer;
use App\Jobs\AppScript\CheckManagerNewSubs as CheckManagerNewSubsJob;
use App\Models\Manager;
use Exception;
use stdClass;
use Tests\TestCase;

class CheckManagerNewSubsTest extends TestCase
{

    private array $data = [
        'data' => [
            'kulpovvvan@gmail.com',
            'ogindon@mail.com',
            'gotnoihj@mail.ru',
            'gregh@mail.ru',
            'p-vgoeksh@mail.ru',
            'iroinb.gieapi@ivan.mail.ru',
        ],
        'file_path' => 'zam'
    ];

    private int $managersWithMailganerListIdCount;
    private array $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managersWithMailganerListIdCount = Manager::whereNotNull('mailganer_list_id')->count();
        $this->manager = Manager::where('nickname', $this->data['file_path'])->first()->toArray();
    }

    public function testSubscribeAllMails(): void
    {
        $mailganer = $this->createMock(Mailganer::class);

        $mailganer->expects( $this->exactly( count($this->data['data']) * $this->managersWithMailganerListIdCount ) )
            ->method('subscriberInfo')
            ->willReturnCallback( function (array $data) {
                $this->assertIsString($data['email']);
                $this->assertIsInt($data['source']);

                $response = new stdClass();
                $response->count = 0;
                return $response;
            });

        $mailganer->expects( $this->exactly( count($this->data['data']) ) )
            ->method('subscribeToList');

        $job = new CheckManagerNewSubsJob( $this->data );
        $job->handle( $mailganer );
    }

    public function testSubscribeNoEmails(): void
    {
        $mailganer = $this->createMock(Mailganer::class);

        $mailganer->expects( $this->exactly( count( $this->data['data'] )) )
            ->method('subscriberInfo')
            ->willReturnCallback( function (array $data) {
                $this->assertIsString($data['email']);
                $this->assertIsInt($data['source']);

                $response = new stdClass();
                $response->count = 1;

                return $response;
            });

        $mailganer->expects( $this->never() )
            ->method('subscribeToList');

        $job = new CheckManagerNewSubsJob( $this->data );
        $job->handle( $mailganer );
    }

    public function testThrowsExceptionFromsubscriberInfo(): void
    {
        $mailganer = $this->createMock(Mailganer::class);

        $mailganer->expects( $this->exactly( 2) )
            ->method('subscriberInfo')
            ->willReturnCallback( function (array $data) {
                $this->assertIsString($data['email']);
                $this->assertIsInt($data['source']);

                $response = new stdClass();
                $response->count = 1;

                if ( $data['email'] === $this->data['data'][1]) {
                    throw new Exception('Ошибочка');
                }

                return $response;
            });

        $mailganer->expects( $this->never() )
            ->method('subscribeToList');

        $job = new CheckManagerNewSubsJob( $this->data );
        $this->expectException( Exception::class );
        $job->handle( $mailganer );
    }

    public function testOnly3FirstEmailsSubscribed(): void
    {
        $mailganer = $this->createMock(Mailganer::class);

        $count = (3 * $this->managersWithMailganerListIdCount) + count($this->data['data']) - 3;
        $mailganer->expects( $this->exactly( $count ) )
            ->method('subscriberInfo')
            ->willReturnCallback( function (array $data) {
                $this->assertIsString($data['email']);
                $this->assertIsInt($data['source']);

                $response = new stdClass();
                if ( array_search($data['email'], $this->data['data']) <= 2) {
                    $response->count = 0;
                } else {
                    $response->count = 1;
                }

                return $response;
            });

        $mailganer->expects( $this->exactly(3) )
            ->method('subscribeToList');

        $job = new CheckManagerNewSubsJob( $this->data );
        $job->handle( $mailganer );
    }
}
