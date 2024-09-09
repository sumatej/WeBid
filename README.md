# README

When making duplicate auctions, we need to make simlinks to the original uploads folder.

Use the `create-simlinks.sh` to create them. Args.:
1. ID of the original auction.
1. ID of the first cloned auction. Usually named #2.
1. Total number of item auctions. Usually the index of the last duplicated item.

E.g., `cd uploaded && bash create-simlinks.sh 600 700 5` will create 4 simlinks:
* 700 -> 600
* 701 -> 600
* 702 -> 600
* 703 -> 600


## Extract Data to Excel

- Go to phpmyadmin
- Run query
- select export and confirm

Query (modify date):
```sql
SELECT
    webid_auctions.title,
    webid_users.email,
    webid_bids.bid
FROM `webid_auctions`
left join webid_bids on webid_auctions.current_bid_id = webid_bids.id
left join webid_users on webid_bids.bidder = webid_users.id
where webid_auctions.ends < '2024-08-22';
```
