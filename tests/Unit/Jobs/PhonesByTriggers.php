<?php

namespace Tests\Unit\Jobs;

use Faker\Factory;
use Tests\Unit\AbstractTestCase;
use App\Helpers\Api\Gmail;
use Google\Service\Gmail\ListMessagesResponse;
use App\Jobs\Gmail\PhonesByTriggers as PhonesByTriggersJob;

class PhonesByTriggers extends AbstractTestCase
{
    private array $triggers = [
        '#KLS0649217',
        '#KLS0663900',
        '#KLS0693205',
        '#KLS0651348',
        '#KLS0672590',
        '#ART0647453',
        '#KLS0709384',
    ];

    private const MANAGER = 'zam';

    private Gmail $gmail;
    private $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gmail = $this->createMock(Gmail::class);
        $this->faker = Factory::create();
    }

    /**
     * @throws \ReflectionException
     */
    public function testFindAllData(): void
    {
        $this->gmail->expects( $this->exactly(count($this->triggers)) )
            ->method('queryMessages')
            ->willReturn( new ListMessagesResponse );

        $this->gmail->expects( $this->exactly(count($this->triggers)) )
            ->method('messagesTextIterator')
            ->willReturnCallback( function ( ListMessagesResponse $list ) {
                yield [
                    'from' => $this->generateEmailAddressText(),
                    'to' => $this->generateEmailAddressText(),
                    'text' => $this->generateEmailTextWith1EmailAnd1Phone(),
                ];
            });

        $job = new PhonesByTriggersJob( self::MANAGER );

        $this->setObjectProperty($job, 'triggers', $this->triggers);
        $this->setObjectProperty($job, 'gmail_api', $this->gmail);

        $messagesListIterator = $this->getObjectMethod(PhonesByTriggersJob::class, 'messagesListIterator');
        $messagesListIterator->invokeArgs($job, []);

        $parsed_data = $this->getObjectProperty($job, 'parsed_data');

        $this->assertCount( count($this->triggers), $parsed_data );

        foreach ( $parsed_data as $email => $triggers) {
            foreach ($triggers as $values) {
                foreach ($values as $value) {
                    $this->assertCount(1, $value['emails']);
                    $this->assertCount(1, $value['phones']);
                }
            }
        }
    }

    private function generateEmailAddressText(): string
    {
        return sprintf("%s <%s>", $this->faker->name(), $this->faker->email());
    }

    private function generateEmailTextWith1EmailAnd1Phone(): string
    {
        return $this->faker->text(50) .
            ' ' . $this->faker->email() . ' ' .
            $this->faker->text(50) . ' ' .
            '+7 (918) 231-95 32';
    }

}
