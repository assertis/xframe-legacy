<?php
use PHPUnit\Framework\TestCase;

/**
 * @author Michał Tatarynowicz <michal.tatarynowicz@assertis.co.uk>
 */
class FormTest extends TestCase
{
    public function testXmlSanitization()
    {
        global $_SESSION;

        $_SESSION = [
            'field' => null,
            'error' => null,
        ];

        $form = new Form();

        $actual = $form->getXML([
            '/foo' => 'bar'
        ]);

        self::assertEquals("<form><f--foo name='/foo'>bar</f--foo></form>", $actual);
    }
}
