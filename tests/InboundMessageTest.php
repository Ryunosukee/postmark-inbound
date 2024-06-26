<?php

namespace dcorreah\Postmark\Tests;

use dcorreah\Postmark\InboundMessage;
use dcorreah\Postmark\Support\PostmarkDate;
use PHPUnit\Framework\TestCase;

class InboundMessageTest extends TestCase
{
    private function validJson($overrides = [])
    {
        return json_encode(
            array_merge(
                json_decode(file_get_contents('./tests/fixtures/inbound.json'), true),
                $overrides
            )
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = new InboundMessage($this->validJson());
    }

    /** @test */
    public function a_valid_json_source_is_required()
    {
        $this->expectException(\InvalidArgumentException::class);
        new InboundMessage('not-a-valid-json-resource');
    }

    /** @test */
    public function getting_unknown_getter_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->message->doesNotExist;
    }

    /** @test */
    public function message_has_a_date()
    {
        $this->assertInstanceOf(PostmarkDate::class, $this->message->date);
        $this->assertEquals('2017-09-06 19:11:00 +0200', $this->message->date->format('Y-m-d H:i:s O'));
    }

    /** @test */
    public function can_get_the_original_date_from_the_message()
    {
        $this->assertEquals('Wed, 6 Sep 2017 19:11:00 +0200', $this->message->originalDate);
    }

    /** @test */
    public function message_has_a_timezone()
    {
        $this->assertEquals('+02:00', $this->message->timezone);
    }

    /** @test */
    public function message_has_a_sender()
    {
        $this->assertEquals('john@example.com', $this->message->from->email);
        $this->assertEquals('John Doe', $this->message->from->name);
        $this->assertEquals('John Doe <john@example.com>', $this->message->from->full);
    }

    /** @test */
    public function message_has_to_recpients()
    {
        $this->assertEquals(1, $this->message->to->count());
        $this->assertEquals('jane+ahoy@inbound.postmarkapp.com', $this->message->to->first()->email);
        $this->assertEquals('Jane Doe', $this->message->to->first()->name);
    }

    /** @test */
    public function message_has_cc_recpients()
    {
        $this->assertEquals(2, $this->message->cc->count());
        $this->assertEquals('sample.cc@example.com', $this->message->cc->first()->email);
        $this->assertEquals('Full name Cc', $this->message->cc->first()->name);
        $this->assertEquals('another.cc@example.com', $this->message->cc->last()->email);
        $this->assertEquals('Another Cc', $this->message->cc->last()->name);
    }

    /** @test */
    public function message_has_bcc_recpients()
    {
        $this->assertEquals(1, $this->message->bcc->count());
        $this->assertEquals('sample.bcc+ahoy@inbound.postmarkapp.com', $this->message->bcc->first()->email);
        $this->assertEquals('Full name Bcc', $this->message->bcc->first()->name);
        $this->assertEquals('ahoy', $this->message->bcc->first()->mailboxHash);
    }

    /** @test */
    public function message_has_a_subject()
    {
        $this->assertEquals('Postmark inbound message test', $this->message->subject);
    }

    /** @test */
    public function message_can_have_an_empty_subject()
    {
        $this->message = new InboundMessage($this->validJson([
            'Subject' => '',
        ]));

        $this->assertEmpty($this->message->subject);
    }

    /** @test */
    public function incorrect_date_formats_posted_by_postmark_should_pass()
    {
        $this->message = new InboundMessage($this->validJson([
            'Date' => 'Fri, 27 Apr 2018 19:00:00 +0200 (CEST)',
        ]));
        $this->assertEquals('2018-04-27 19:00:00', $this->message->date->format('Y-m-d H:i:s'));

        $this->message = new InboundMessage($this->validJson([
            'Date' => 'Fri, 27 Apr 2018 19:00:00 +0100 (West-Europe (stand',
        ]));
        $this->assertEquals('2018-04-27 19:00:00', $this->message->date->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function message_has_an_id()
    {
        $this->assertEquals('a123456-b1234-c123456-d1234', $this->message->messageId);
    }

    /** @test */
    public function message_has_a_tag()
    {
        $this->assertEquals('test-tag', $this->message->tag);
    }

    /** @test */
    public function message_has_an_original_recipient()
    {
        $this->assertEquals('1234+ahoy@inbound.postmarkapp.com', $this->message->originalRecipient);
    }

    /** @test */
    public function message_has_a_text_body()
    {
        $this->assertEquals('[ASCII]', $this->message->textBody);
    }

    /** @test */
    public function message_has_a_html_body()
    {
        $this->assertEquals('<html></html>', $this->message->htmlBody);
    }

    /** @test */
    public function message_has_stripped_text_reply()
    {
        $this->assertEquals('Okay, thank you for testing this inbound message!', $this->message->strippedTextReply);
    }

    /** @test */
    public function message_has_a_reply_to_address()
    {
        $this->assertEquals('reply-to@example.com', $this->message->replyTo);
    }

    /** @test */
    public function message_has_headers()
    {
        $this->assertEquals(8, $this->message->headers->count());
        $this->assertEquals('<test-message-id@mail.example.com>', $this->message->headers->get('Message-ID'));
        $this->assertEquals('1.0', $this->message->headers->get('MIME-Version'));
        $this->assertEquals('Pass (sender SPF authorized)', $this->message->headers->get('Received-SPF'));
        $this->assertEquals('-0.1', $this->message->headers->get('X-Spam-Score'));
        $this->assertEquals('No', $this->message->headers->get('X-Spam-Status'));
        $this->assertEquals('DKIM_SIGNED,DKIM_VALID,SPF_PASS', $this->message->headers->get('X-Spam-Tests'));
        $this->assertEquals('SpamAssassin 3.3.1', $this->message->headers->get('X-Spam-Checker-Version'));
    }

    /** @test */
    public function message_has_attachments()
    {
        $this->assertEquals(2, $this->message->attachments->count());

        $attachment = $this->message->attachments->first();
        $this->assertEquals('myimage.png', $attachment->name);
        $this->assertEquals('image/png', $attachment->contentType);
        $this->assertEquals(4096, $attachment->contentLength);
        $this->assertEquals('myimage.png@01CE7342.75E71F80', $attachment->contentId);

        $attachment = $this->message->attachments->last();
        $this->assertEquals('test.txt', $attachment->name);
        $this->assertEquals('plain/text', $attachment->contentType);
        $this->assertEquals(8, $attachment->contentLength);
        $this->assertNull($attachment->contentId);
        $this->assertEquals('test', $attachment->content());
    }

    /** @test */
    public function message_has_no_attachments_when_not_present_in_json_payload()
    {
        $this->message = new InboundMessage('{}');
        $this->assertEquals(0, $this->message->attachments->count());

        $this->message = new InboundMessage($this->validJson([
            'Attachments' => [],
        ]));
        $this->assertEquals(0, $this->message->attachments->count());
    }

    /** @test */
    public function get_message_id_from_headers()
    {
        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'Message-Id',
                'Value' => '<message-id-from-headers>',
            ]],
        ]));

        $this->assertEquals('<message-id-from-headers>', $this->message->messageIdFromHeaders);
    }

    /** @test */
    public function can_retrieve_spam_score_from_message()
    {
        $this->message = new InboundMessage($this->validJson([
            'Headers' => [],
        ]));
        $this->assertSame(0.0, $this->message->spamScore);

        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'X-Spam-Score',
                'Value' => '1.0',
            ]],
        ]));

        $this->assertSame(1.0, $this->message->spamScore);

        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'X-Spam-Score',
                'Value' => '-1.0',
            ]],
        ]));

        $this->assertSame(-1.0, $this->message->spamScore);
    }

    /** @test */
    public function can_retrieve_spam_status_value()
    {
        $this->message = new InboundMessage($this->validJson([
            'Headers' => [],
        ]));
        $this->assertEquals('No', $this->message->spamStatus);

        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'X-Spam-Status',
                'Value' => 'Yes',
            ]],
        ]));

        $this->assertEquals('Yes', $this->message->spamStatus);
    }

    /** @test */
    public function can_determine_if_message_is_spam()
    {
        $this->message = new InboundMessage($this->validJson([
            'Headers' => [],
        ]));
        $this->assertFalse($this->message->isSpam);

        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'X-Spam-Status',
                'Value' => 'Yes',
            ]],
        ]));
        $this->assertTrue($this->message->isSpam);

        $this->message = new InboundMessage($this->validJson([
            'Headers' => [[
                'Name' => 'X-Spam-Status',
                'Value' => 'yes',
            ]],
        ]));
        $this->assertTrue($this->message->isSpam);
    }

    /** @test */
    public function trying_to_access_the_headers_attribute_should_return_empty_collection_when_not_present()
    {
        $this->message = new InboundMessage('{}');

        $this->assertSame([], $this->message->headers->toArray());
    }
}
