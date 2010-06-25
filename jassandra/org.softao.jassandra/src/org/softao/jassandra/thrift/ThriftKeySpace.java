/*
 * Copyright (C) 2010 Softao.Org
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
package org.softao.jassandra.thrift;

import java.util.Map;
import java.util.Map.Entry;

import org.apache.cassandra.thrift.NotFoundException;
import org.apache.cassandra.thrift.Cassandra.Client;
import org.apache.thrift.TException;
import org.softao.jassandra.IColumnFamily;
import org.softao.jassandra.IConnection;
import org.softao.jassandra.IKeySpace;
import org.softao.jassandra.JassandraException;

import com.google.common.collect.ImmutableMap;
import com.google.common.collect.ImmutableMap.Builder;

/**
 * An implementation of {@link IKeySpace}, which is used internally for
 * <code>Thrift</code> implementation.
 */
class ThriftKeySpace implements IKeySpace {
	private final ThriftConnection mConnection;
	private final String mName;
	private Map<String, IColumnFamily> mColumnFamilies;

	/**
	 * @param connection
	 * @param name
	 */
	public ThriftKeySpace(ThriftConnection connection, String name) {
		if (connection == null) {
			throw new NullPointerException("connection");
		}
		if (name == null) {
			throw new NullPointerException("name");
		}
		
		mConnection = connection;
		mName = name;
	}

	@Override
	public IConnection getConnection() {
		return mConnection;
	}

	@Override
	public String getName() {
		return mName;
	}

	@Override
	public IColumnFamily getColumnFamily(String name) throws JassandraException {
		ensureColumnFamilies();

		if (mColumnFamilies.containsKey(name)) {
			return mColumnFamilies.get(name);
		}

		throw new JassandraException(String.format(
				"ColumnFamily %1$s cannot be found.", name));
	}

	@Override
	public Map<String, IColumnFamily> getColumnFamilies()
			throws JassandraException {
		ensureColumnFamilies();

		return mColumnFamilies;
	}

	private void ensureColumnFamilies() throws JassandraException {
		if (mColumnFamilies != null) {
			return;
		}
		Builder<String, IColumnFamily> builder = new ImmutableMap.Builder<String, IColumnFamily>();

		Client client = mConnection.getClient();
		try {
			Map<String, Map<String, String>> map = client
					.describe_keyspace(mName);

			for (Entry<String, Map<String, String>> entry : map.entrySet()) {
				String name = entry.getKey();
				Map<String, String> cfMap = entry.getValue();
				String cfType = cfMap.get("Type");
				boolean isSuper = cfType.equalsIgnoreCase("Super");
				builder.put(name, new ThriftColumnFamily(this, name, isSuper));
			}

			mColumnFamilies = builder.build();
		} catch (NotFoundException e) {
			throw new JassandraException(String.format(
					"KeySpace %1$s cannot be found.", mName), e);
		} catch (TException e) {
			throw new JassandraException("Access cassanra failed.", e);
		}
	}
}
