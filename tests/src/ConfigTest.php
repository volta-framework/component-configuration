<?php
declare(strict_types=1);

namespace src;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

use Volta\Component\Configuration\Config;

/**
 * @covers \Volta\Component\Configuration\Config
 * @covers \Volta\Component\Configuration\Exception
 * @covers \Volta\Component\Configuration\Key
 */
class ConfigTest  extends TestCase
{
    private array $data = [
        'php-file' => DATA_DIR . 'ConfigData.php',
        'json-file' => DATA_DIR . 'ConfigData.json',
        'json-string' => '{
                "key-1" : "value-1",
                "key-2" : {
                    "key-2-1": "value-2-1",
                    "key-2-2": {
                        "key-2-2-1": "value-2-2-1"
                    }
                },
                "key-3" : {}
            }',
        'php-array' => [
            'key-1' => 'value-1',
            'key-2' => [
                'key-2-1' => 'value-2-1',
                'key-2-2' => [
                    'key-2-2-1' => 'value-2-2-1',
                ],
            ],
            'key-3' => [],
        ]
    ];

    #[TestDox('Test(s) for loading different configuration formats(PHP-FILE, PHP-ARRAY, JSON-FILE, JSON-STRING)')]
    public function testSuccesFullLoad(): void
    {
        foreach( $this->data as $type => $optionData) {
            $conf = new Config($optionData);
            $this->assertEquals(count($conf->getOptions()), 3);
        }
    }

    #[TestDox('Test(s) for loading incorrect configuration formats')]
    public function testSuccesFailedLoad(): void
    {
        $this->expectException(\Volta\Component\Configuration\Exception::class);
        $this->expectExceptionMessage('Json error - Syntax error, malformed JSON');
        $conf = new Config('not existing file');

    }

    #[TestDox('Test(s) for GETTING configuration option values in different ways')]
    public function testGetOption(): void
    {
        foreach( $this->data as $type => $optionData) {
            $conf = new Config( $optionData);
            $this->assertEquals($conf->getOption('key-1'), 'value-1');
            $this->assertEquals($conf['key-1'], 'value-1');
            $this->assertEquals($conf->getOption('key-2.key-2-1'), 'value-2-1');
            $this->assertEquals($conf->get('key-2.key-2-1'), 'value-2-1');
            $this->assertEquals($conf['key-2.key-2-1'], 'value-2-1');

            $this->assertEquals($conf->getOption('none.existing.key.with.default.value', 'default.value'), 'default.value');
            $this->assertEquals($conf['key-3'], []);
            $this->assertTrue($conf->hasOption('key-3'));
            $this->assertFalse($conf->hasOption('key-4'));

        }
    }

    #[TestDox('Test(s) for SETTING configuration option values in different ways')]
    public function testSetOption(): void
    {
        foreach( $this->data as $type => $optionData) {
            $conf = new Config($optionData);
            $conf->setOption('key-1', 'bogus', true);
            $this->assertEquals($conf->getOption('key-1'), 'bogus');
            $this->assertEquals($conf['key-1'], 'bogus');
            $this->expectException(\Volta\Component\Configuration\Exception::class);
            $this->expectExceptionMessage('Option "key-1" already set, called in "src\ConfigTest::testSetOption()"!');
            $conf->setOption('key-1', 'bogus again');
            $this->expectException(\Volta\Component\Configuration\Exception::class);
            $this->expectExceptionMessage('Option "key-1" already set, called in "Volta\Component\Configuration\Config::offsetSet()"!');
            $conf->setOption('key-1', 'bogus again');
            $conf->set('key-1', 'bogus again');
            $conf['key-1'] = 'bogus again';
        }
    }


    #[TestDox('Test(s) for setting options in the "not allowed" list')]
    public function testAllowedOption(): void
    {
        $conf = new Config();
        $conf->setAllowedOptions(['key-1']);
        $conf->setOption('key-1', 'bogus'); // allowed
        $this->assertEquals($conf['key-1'], 'bogus');

        $this->expectException(\Volta\Component\Configuration\Exception::class);
        $this->expectExceptionMessage('Option "key-2" not allowed, called in "src\ConfigTest::testAllowedOption()"!');
        $conf->setOption('key-2', 'bogus');
    }

    #[TestDox('Test(s) for missing options in the "required" list')]
    public function testMissingRequiredOption(): void
    {
        $conf = new Config();
        $conf->setRequiredOptions(['key-2.key-2-2.bogus']);
        $this->expectException(\Volta\Component\Configuration\Exception::class);
        $this->expectExceptionMessage('Required option "key-2.key-2-2.bogus" is missing, called in "src\ConfigTest::testMissingRequiredOption()"!');
        $conf->setOptions([]);
    }

    #[TestDox('Test(s) for NOT missing options the "required" list')]
    public function testRequiredOption(): void
    {
        $conf = new Config();
        $conf->setRequiredOptions(['key-2.key-2-2']);
        $conf->setOptions( $this->data['php-array']);
        $this->assertEquals($conf['key-1'], 'value-1');

    }

    #[TestDox('Test(s) for unsetting options in the "required" list')]
    public function testUnsetRequiredOption(): void
    {
        $conf = new Config();
        $conf->setRequiredOptions(['key-1']);
        $conf->setOption('key-1', 'bogus');
        $this->assertEquals($conf['key-1'], 'bogus');

        $this->assertTrue($conf->hasOption('key-1'));
        $this->expectException(\Volta\Component\Configuration\Exception::class);
        $this->expectExceptionMessage('Cannot unset a required option "key-1", called in "src\ConfigTest::testUnsetRequiredOption()"!');

        // removing is not allowed here
        $conf->unsetOption('key-1');
    }

    #[TestDox('Test(s) for cascading configurations')]
    public function testCascadingConfigurations():void
    {
        $confA = new Config(DATA_DIR . 'ConfigData.json');
        $confB = new Config(DATA_DIR . 'ConfigDataCascading.json');
        $this->assertEquals( "value-2-2-1", $confA['key-2.key-2-2.key-2-2-1']);
        $confA->setOptions($confB->getOptions());
        $this->assertEquals( "new value", $confA['key-4']);
        $this->assertEquals( [], $confA['key-3']); // must stil be there
        $this->assertEquals( "value-2-2-1-cascading", $confA['key-2.key-2-2.key-2-2-1']); // has a new value
        $confA = new Config();
        $confA->setAllowedOptions([
            'key-1',
            'key-2',
            'key-2.key-2-1',
            'key-2.key-2-2',
            'key-2.key-2-2.key-2-2-1',
            'key-3',
        ]);
        $confA->setOptions(DATA_DIR . 'ConfigData.json');
        $this->expectException(\Volta\Component\Configuration\Exception::class);
        $this->expectExceptionMessage('Option "key-4" not allowed, called in "src\ConfigTest::testCascadingConfigurations()"!');
        $confA->setOptions($confB->getOptions());
    }

}