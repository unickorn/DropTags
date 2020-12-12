<?php

declare(strict_types=1);
namespace Unickorn\DropTags;

use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\plugin\PluginBase;
use function ord;

class Main extends PluginBase implements Listener
{
	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof BatchPacket){
			$packet->decode();

			foreach($packet->getPackets() as $buf){
				$pk = PacketPool::getPacketById(ord($buf[0]));
				if($pk instanceof MoveActorAbsolutePacket){
					$pk->setBuffer($buf);
					$pk->decode();

					$level = $event->getPlayer()->getLevel();
					$entity = $level->getEntity($pk->entityRuntimeId);
					if($entity instanceof ItemEntity){
						foreach($level->getNearbyEntities($entity->getBoundingBox()->expandedCopy(0.5, 0.5, 0.5), $entity) as $nearbyEntity){
							if($nearbyEntity instanceof ItemEntity && !$nearbyEntity->isClosed() && $entity->getItem()->equals($nearbyEntity->getItem(), true, true)){
								$newCount = $nearbyEntity->getItem()->getCount() + $entity->getItem()->getCount();
								if($newCount > 255) continue; // Count is nbtSerialized as ByteTag
								$nearbyEntity->getLevel()->dropItem($nearbyEntity, $nearbyEntity->getItem()->setCount($newCount), $nearbyEntity->getMotion());
								$nearbyEntity->close();
								$entity->close();
								break;
							}
						}
					}
				}
			}
		}
	}

	public function onItemSpawn(ItemSpawnEvent $event) : void{
		$entity = $event->getEntity();
		$entity->setNameTag($entity->getItem()->getCount() . "x " . $entity->getItem()->getName());
		$entity->setNameTagAlwaysVisible();
	}
}
