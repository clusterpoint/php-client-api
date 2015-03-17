<?php

// according to --> http://code.google.com/apis/protocolbuffers/docs/encoding.html

// !!!!!!!!!!!!!!!!!!!!!!!
// patreiz supporteets ir tikai encoding/decoding prieksh stringiem, citiem datu tipiem izveidosim peec vajadziibas
// !!!!!!!!!!!!!!!!!!!!!!!

function ProtoBuf_Bytes2Varint($stream, $offset, &$result) // ret <= 0 - error
{
	$moved = 0;
	$result = 0;
	$quot = 1;

	while (true)
	{
		$byte = ord($stream[$offset + $moved]);
		$moved++;
		$result += $quot * ($byte & 0x7F);
		$quot *= 128;
		if (($byte & 0x80) == 0)
			break;
	}

	return $moved;
}

function ProtoBuf_Bytes2Fixed($data, $size)
{
	$ret = 0;
	$shift = 0;

	while ($size > 0)
	{
		$byte = ord($data[0]);
		$ret += $byte << $shift;
		$size -= 1;
		$shift += 8;
		$data = substr($data, 1);
	}

	return $ret;
}

function ProtoBuf_Varint2Bytes($value)
{
	$ret = "";
	while ($value > 0)
	{
		$byte = $value % 128;
		$value = floor($value / 128);
		if ($value > 0)
			$byte |= 0x80;
		$ret .= chr($byte);
	}
	return $ret;
}

function ProtoBuf_Fixed2Bytes($value, $size)
{
	$ret = "";
	while ($size > 0)
	{
		$byte = $value % 256;
		$size -= 1;
		$value = $value >> 8;
		$ret .= chr($byte);
	}
	return $ret;
}

class ProtoBuf_Field
{
	const ProtoBuf_WireType_Varint = 0;
	const ProtoBuf_WireType_64bit = 1;
	const ProtoBuf_WireType_LengthDelimited = 2;
	const ProtoBuf_WireType_StartGroup = 3;
	const ProtoBuf_WireType_EndGroup = 4;
	const ProtoBuf_WireType_32bit = 5;

	public $fn; // field number
	public $wt; // wire type
	public $data; // actual data
	public $fixed64_is_32bit; // if fixed64 is in fact 32bit value, this is just to improve compatibility with 32bit php, which does not support 64

	public function FromBytes($stream, $offset) // ret <= 0 - error
	{
		$moved = 0;
		$vi = 0;
		$moved += $parsed = ProtoBuf_Bytes2Varint($stream, $offset + $moved, $vi);
		if ($parsed <= 0)
			return $parsed;

		//echo "vi $vi\n";
		$this->wt = ($vi & 0x07);
		$this->fn = ($vi >> 3);
		//echo "field wt " . $this->wt . " fn " . $this->fn . "\n";

		switch ($this->wt)
		{
			case ProtoBuf_Field::ProtoBuf_WireType_Varint: // TODO: in case we need this information, create get function
				$this->data = 0;
				$moved += $parsed = ProtoBuf_Bytes2Varint($stream, $offset + $moved, $this->data);
				break;
			case ProtoBuf_Field::ProtoBuf_WireType_64bit:
				$this->data = substr($stream, $offset + $moved, 8);
				$high4 = ProtoBuf_Bytes2Fixed(substr($this->data, 4), 4);
				if ($high4 == 0)
				{
					$this->fixed64_is_32bit = true;
					$this->data = ProtoBuf_Bytes2Fixed($this->data, 4);
				}
				else
				{
					$this->fixed64_is_32bit = false;
					$this->data = ProtoBuf_Bytes2Fixed($this->data, 8);
				}
				$moved += 8;
				break;
			case ProtoBuf_Field::ProtoBuf_WireType_LengthDelimited:
				$moved += $parsed = ProtoBuf_Bytes2Varint($stream, $offset + $moved, $vi);
				if ($parsed <= 0)
					return $parsed;
				$this->data = substr($stream, $offset + $moved, $vi);
				$moved += $vi;
				break;
			/*case ProtoBuf_Field::ProtoBuf_WireType_StartGroup: // TODO: in case we need this information, create get function
				break;
			case ProtoBuf_Field::ProtoBuf_WireType_EndGroup: // TODO: in case we need this information, create get function
				break;*/
			case ProtoBuf_Field::ProtoBuf_WireType_32bit:
				$this->data = substr($stream, $offset + $moved, 4);
				$this->data = ProtoBuf_Bytes2Fixed($this->data, 4);
				$moved += 4;
				break;
			default:
				die("[lib_protobuf.inc] Unrecognized protobuf wire type '" . $this->wt . "'\n");
				break;
		}

		return $moved;
	}

	public function ToBytes()
	{
		$ret = ProtoBuf_Varint2Bytes(($this->fn << 3) | $this->wt);
		if ($this->wt == ProtoBuf_Field::ProtoBuf_WireType_Varint)
			$ret .= ProtoBuf_Varint2Bytes($this->data);
		elseif ($this->wt == ProtoBuf_Field::ProtoBuf_WireType_64bit)
		{
			if ($this->fixed64_is_32bit)
			{
				$ret .= ProtoBuf_Fixed2Bytes($this->data, 4);
				$ret .= ProtoBuf_Fixed2Bytes(0, 4);
			}
			else
				$ret .= ProtoBuf_Fixed2Bytes($this->data, 8);
		}
		elseif ($this->wt == ProtoBuf_Field::ProtoBuf_WireType_LengthDelimited)
		{
			$ret .= ProtoBuf_Varint2Bytes(strlen($this->data));
			$ret .= $this->data;
		}
		elseif ($this->wt == ProtoBuf_Field::ProtoBuf_WireType_32bit)
			$ret .= ProtoBuf_Fixed2Bytes($this->data, 4);
		return $ret;
	}
}

class SimpleProtoBuf
{
	private $fields;

	public function __construct()
	{
		$this->fields = array();
	}

	public function FromBytes($stream)
	{
		/*$f = fopen("/tmp/stream-dump.txt", "a");
		fwrite($f, $stream);
		fwrite($f, "\n------\n\n");
		fclose($f);*/
		//file_put_contents("/tmp/stream-dump.txt", $stream);
		$used = 0;
		while ($used < strlen($stream))
		{
			$f = new ProtoBuf_Field();
			$used += $parsed = $f->FromBytes($stream, $used);
			if ($parsed > 0)
				$this->fields[] = $f;
			else
				break;
		}
	}

	public function ToBytes()
	{
		$ret = "";
		for ($i = 0; $i < count($this->fields); $i++)
			$ret .= $this->fields[$i]->ToBytes();
		return $ret;
	}

	public function NewField($fn, $wt, $data)
	{
		$f = new ProtoBuf_Field();
		$f->fn = $fn;
		$f->wt = $wt;
		$f->data = $data;
		$this->fields[] = $f;
		return $f;
	}

	public function NewFieldString($fn, $data)
	{
		$this->NewField($fn, ProtoBuf_Field::ProtoBuf_WireType_LengthDelimited, $data);
	}
	
	public function NewFieldFixed64($fn, $value)
	{
		$f = $this->NewField($fn, ProtoBuf_Field::ProtoBuf_WireType_64bit, 0);
		$f->data = $value;
		$f->fixed64_is_32bit = false;
	}
	
	public function NewFieldFixed64_is_32bit($fn, $value)
	{
		$f = $this->NewField($fn, ProtoBuf_Field::ProtoBuf_WireType_64bit, 0);
		$f->data = $value;
		$f->fixed64_is_32bit = true;
	}

	public function GetFieldString($fn)
	{
		$ret = false;
		for ($i = 0; $ret === false && $i < count($this->fields); $i++)
			if ($this->fields[$i]->fn == $fn && $this->fields[$i]->wt == ProtoBuf_Field::ProtoBuf_WireType_LengthDelimited)
				$ret = $this->fields[$i]->data;
		return $ret;
	}
}

/*
$s = chr(0x12) . chr(0x07) . chr(0x74) . chr(0x65) . chr(0x73) . chr(0x74) . chr(0x69) . chr(0x6e) . chr(0x67);
$b = new SimpleProtoBuf();
$b->FromBytes($s);

print_r($b);

$r = $b->ToBytes();
echo strlen($r) . "\n" . $r;

$vi = 0;
echo ProtoBuf_Bytes2Varint(chr(0xac) . chr(0x02), 0, $vi) . "->" . $vi . "\n";
*/

/*error_reporting(E_ALL);

$data = file_get_contents("stream-dump.txt");
$protos = explode("\n------\n\n", $data);
echo count($protos);

for ($i = 0; $i < count($protos); $i++)
{
	echo "-->$i\n";
	$pb = new SimpleProtoBuf();
	$pb->FromBytes($protos[$i]);
	//print_r($pb);
}

echo "\n";*/

/*$p = new SimpleProtoBuf();
$p->NewFieldFixed64_is_32bit(1, 123654789);
$p->NewFieldFixed64(2, 123654789147852);
$s = $p->ToBytes();

$p2 = new SimpleProtoBuf();
$p2->FromBytes($s);
print_r($p2);*/

?>