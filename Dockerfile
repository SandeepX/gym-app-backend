FROM ubuntu:latest
LABEL authors="mr.incognito"

ENTRYPOINT ["top", "-b"]
