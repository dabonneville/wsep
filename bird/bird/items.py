# -*- coding: utf-8 -*-

# Define here the models for your scraped items
#
# See documentation in:
# http://doc.scrapy.org/en/latest/topics/items.html

from scrapy.item import Item,Field


class BirdItem(Item):
	latitude=Field()
	longitude=Field()
	startDate=Field()
	endDate=Field()
	title=Field()
	eventid=Field()
	url=Field()
	sort=Field()
	info=Field()
	imgSrc=Field()
	price=Field()
	address=Field()
	city=Field()
	time=Field()
	category=Field()
	area=Field()
	today=Field()

