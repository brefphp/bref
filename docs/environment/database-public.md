---
introduction: Configure RDS to expose a RDS database publicly so that you can access it from your computer.
---

# Exposing a RDS database on the internet

[&#9664; Back to the "Databases" documentation](database.mdx).

---

**Exposing a database on the internet is insecure.**

A secure alternative is to set up an SSH tunnel instead, for example **[using 7777](https://port7777.com/?utm_source=bref)**.

## Limitations

Aurora Serverless databases cannot be made publicly accessible.

## How to

Open the RDS instance in the [RDS console](https://console.aws.amazon.com/rds/home#databases:):

- click "Modify"
- enable "Public accessibility"
- click "Continue"
- select "Apply immediately" (**do not skip this step**)
- click "Modify DB Instance"

Now click the *security group* of the instance:

- open the "Inbound" tab
- click "Edit"
- add a rule: select "MySQL/Aurora" (or PostgreSQL) and set "Anywhere" as the source (you could also set your public IP for increased security)
- save

Connect to your database using your favorite tool. For example using `mysql` in the CLI:

```bash
mysql -h<endpoint> -u<root user> -p <database>
```

**Remember to revert those changes once you are done!**
