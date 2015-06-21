<?php
/**
 * Created by PhpStorm.
 * User: Lukas
 * Date: 20.06.2015
 * Time: 22:44
 */

namespace ManiaControl\Sockets;


use ManiaControl\Callbacks\Listening;
use ManiaControl\ManiaControl;
use React\EventLoop\Factory;

class SocketHandler {

	/** @var ManiaControl $maniaControl */
	private $maniaControl = null;
	/** @var Listening[] $socketListenings */
	private $socketListenings = array();

	/**
	 * Create a new Socket Handler Instance
	 *
	 * @param ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;

	}

	public function createSocket() {
		$loop   = Factory::create();
		$server = stream_socket_server('tcp://127.0.0.1:19999');
		stream_set_blocking($server, 0);

		$loop->addReadStream($server, function ($server) use ($loop) {
			$conn = stream_socket_accept($server);
			$data = "HTTP/1.1 200 OK\r\nContent-Length: 3\r\n\r\nHi\n";
			$loop->addWriteStream($conn, function ($conn) use (&$data, $loop) {
				$written = fwrite($conn, $data);
				if ($written === strlen($data)) {
					fclose($conn);
					$loop->removeStream($conn);
				} else {
					$data = substr($data, 0, $written);
				}
			});
		});

		$loop->addPeriodicTimer(5, function () {
			$memory    = memory_get_usage() / 1024;
			$formatted = number_format($memory, 3) . 'K';
			echo "Current memory usage: {$formatted}\n";
		});

		$loop->tick();
	}

	public function tick() {

	}
}